/*
 * php-etl live execution graph — framework-agnostic, dependency-light widget.
 *
 * Renders a chain's topology (from the bundle's /graph endpoint) with Cytoscape,
 * then keeps it live by one of three means, chosen automatically:
 *   1. Mercure (SSE) push  — when data-mercure-url is present
 *   2. polling /state      — when the execution is still running and no Mercure
 *   3. fully static        — when the execution has finished
 *
 * No build step, no module system: it reads its configuration from the
 * data-* attributes of its root element (rendered by
 * @Oliverde8PhpEtl/observability/graph.html.twig) so any Symfony frontend
 * (EasyAdmin, Sylius, custom) can drop it in. Requires the vendored globals
 * `cytoscape` and `cytoscapeDagre`.
 */
(function () {
  'use strict';

  var STATE_COLORS = {
    NotInit: '#5b6270', Waiting: '#3a4256', Async: '#f5b544',
    Running: '#5b8bff', Stopping: '#26c6a7', Stopped: '#37d67a',
    Failed: '#ff5d5d'
  };
  var TERMINAL = { success: 1, failure: 1 };

  function fmtTime(ms) {
    ms = Math.abs(ms | 0);
    var cent = String(Math.floor((ms % 1000) / 100));
    var s = Math.floor(ms / 1000);
    var sec = String(s % 60);
    if (sec.length < 2) { sec = '0' + sec; }
    var min = String(Math.floor(s / 60));
    if (min.length < 2) { min = '0' + min; }
    return min + ':' + sec + '.' + cent;
  }

  function fmtClock(iso) {
    if (!iso) { return ''; }
    var d = new Date(iso);
    if (isNaN(d.getTime())) { return ''; }
    return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
  }

  function el(tag, cls, html) {
    var n = document.createElement(tag);
    if (cls) { n.className = cls; }
    if (html != null) { n.innerHTML = html; }
    return n;
  }

  function ExecutionGraph(root) {
    this.root = root;
    var d = root.dataset;
    this.cfg = {
      graphUrl: d.graphUrl,
      stateUrl: d.stateUrl,
      logsUrl: d.logsUrl,
      status: (d.status || 'waiting').toLowerCase(),
      pollInterval: parseInt(d.pollInterval || '2000', 10),
      mercureUrl: d.mercureUrl || '',
      mercureTopic: d.mercureTopic || ''
    };
    this.state = {};          // nodeId -> {state,name,in,out,ms,async}
    this.nodes = [];          // topology nodes
    this.selected = null;
    this.logOffset = 0;
    this.logLines = [];
    this.finished = !!TERMINAL[this.cfg.status];
    this.timers = [];
    this.paused = false;
    this.init();
  }

  ExecutionGraph.prototype.init = function () {
    if (!window.cytoscape) {
      this.root.appendChild(el('div', 'etl-graph-error', 'Cytoscape failed to load — check the bundle assets are installed.'));
      return;
    }
    try { cytoscape.use(window.cytoscapeDagre); } catch (e) { /* already registered */ }
    this.buildDom();
    var self = this;
    fetch(this.cfg.graphUrl, { headers: { Accept: 'application/json' } })
      .then(function (r) { return r.json(); })
      .then(function (data) { self.onGraph(data); })
      .catch(function (e) { self.setStatus('failure', 'graph load failed'); self.log('err', String(e)); });
  };

  /* ---------- DOM scaffold ---------- */
  ExecutionGraph.prototype.buildDom = function () {
    var r = this.root;
    r.classList.add('etl-graph');
    r.innerHTML = '';

    var bar = el('div', 'eg-bar');
    var brand = el('div', 'eg-brand');
    this.titleEl = el('div', 'eg-title', 'ETL execution');
    this.subEl = el('div', 'eg-sub', '');
    brand.appendChild(this.titleEl);
    brand.appendChild(this.subEl);
    this.badgeEl = el('span', 'eg-badge', '<span class="eg-dot"></span> …');
    this.connEl = el('span', 'eg-conn', '');
    this.kpiEl = el('div', 'eg-kpis');
    bar.appendChild(brand);
    bar.appendChild(el('div', 'eg-spacer'));
    bar.appendChild(this.kpiEl);
    bar.appendChild(this.connEl);
    bar.appendChild(this.badgeEl);
    r.appendChild(bar);

    var body = el('div', 'eg-body');
    var wrap = el('div', 'eg-graph-wrap');

    var tools = el('div', 'eg-toolbar');
    this.pauseBtn = el('button', 'eg-btn', '⏸');
    var fitBtn = el('button', 'eg-btn', '⤢ Fit');
    var layoutBtn = el('button', 'eg-btn', '⇅ Layout');
    tools.appendChild(this.pauseBtn);
    tools.appendChild(fitBtn);
    tools.appendChild(layoutBtn);
    wrap.appendChild(tools);

    this.cyEl = el('div', 'eg-cy');
    wrap.appendChild(this.cyEl);
    wrap.appendChild(this.legend());
    body.appendChild(wrap);

    this.drawer = el('aside', 'eg-drawer');
    this.drawer.appendChild(el('div', 'eg-empty', 'Click a step to inspect its live state and logs.'));
    body.appendChild(this.drawer);
    r.appendChild(body);

    var foot = el('div', 'eg-foot');
    var fh = el('div', 'eg-foot-head');
    fh.appendChild(el('span', null, 'Live logs'));
    this.feedToggle = el('button', 'eg-btn eg-btn-sm', 'hide');
    fh.appendChild(this.feedToggle);
    foot.appendChild(fh);
    this.feedEl = el('pre', 'eg-feed');
    foot.appendChild(this.feedEl);
    r.appendChild(foot);

    var self = this;
    fitBtn.onclick = function () { self.cy && self.cy.animate({ fit: { padding: 30 } }, { duration: 250 }); };
    layoutBtn.onclick = function () { self.relayout(); };
    this.pauseBtn.onclick = function () { self.paused = !self.paused; self.pauseBtn.textContent = self.paused ? '▶' : '⏸'; };
    this.feedToggle.onclick = function () {
      var hidden = self.feedEl.style.display === 'none';
      self.feedEl.style.display = hidden ? '' : 'none';
      self.feedToggle.textContent = hidden ? 'hide' : 'show';
    };
  };

  ExecutionGraph.prototype.legend = function () {
    var L = el('div', 'eg-legend');
    var items = [['Waiting', 'Waiting'], ['Running', 'Running'], ['Async', 'Async'], ['Stopped', 'Done'], ['Failed', 'Failed']];
    items.forEach(function (it) {
      L.appendChild(el('span', null, '<i style="background:' + STATE_COLORS[it[0]] + '"></i>' + it[1]));
    });
    // Split/merge nodes take their color from live state like any other node —
    // the hexagon shape alone marks them as branch-holding, so no swatch here.
    L.appendChild(el('span', null, 'Split ⬡'));
    return L;
  };

  /* ---------- graph bootstrap ---------- */
  ExecutionGraph.prototype.onGraph = function (data) {
    var exec = data.execution || {};
    this.titleEl.textContent = exec.name || 'ETL execution';
    var subParts = ['execution #' + (exec.id || '')];
    if (exec.username) { subParts.push('queued by ' + exec.username); }
    var started = fmtClock(exec.startTime || exec.createTime);
    if (started) { subParts.push('started ' + started); }
    this.subEl.textContent = subParts.join(' · ');
    if (exec.status) { this.cfg.status = String(exec.status).toLowerCase(); this.finished = !!TERMINAL[this.cfg.status]; }
    this.setStatus(this.cfg.status);
    if (data.error) { this.log('warn', 'topology: ' + data.error); }

    this.nodes = (data.graph && data.graph.nodes) || [];
    var edges = (data.graph && data.graph.edges) || [];
    this.state = data.state || {};

    var self = this;
    this.cy = cytoscape({
      container: this.cyEl,
      wheelSensitivity: 0.25,
      elements: {
        nodes: this.nodes.map(function (n) {
          return { data: { id: n.id, name: n.name, type: n.type, kind: n.kind, label: self.label(n.id) }, classes: n.kind === 'split' ? 'split' : '' };
        }),
        edges: edges.map(function (e, i) { return { data: { id: 'e' + i, source: e.source, target: e.target } }; })
      },
      style: this.cyStyle(),
      layout: { name: this.nodes.length ? 'dagre' : 'grid', rankDir: 'TB', nodeSep: 45, rankSep: 60 }
    });
    this.cy.on('tap', 'node', function (evt) { self.select(evt.target.id()); });

    this.paint();
    this.cy.ready(function () { self.cy.fit(undefined, 30); });

    this.startLogs();
    if (this.finished) { return; }                 // static
    if (this.cfg.mercureUrl && this.cfg.mercureTopic) { this.startMercure(); }
    else { this.startPolling(); }
  };

  ExecutionGraph.prototype.cyStyle = function () {
    return [
      { selector: 'node', style: {
        'shape': 'round-rectangle', 'width': 168, 'height': 58,
        'background-color': '#171b24', 'border-width': 2, 'border-color': 'data(border)',
        'label': 'data(label)', 'color': '#e6e9f0', 'font-size': 10,
        'text-valign': 'center', 'text-halign': 'center', 'text-wrap': 'wrap',
        'text-max-width': 150, 'text-justification': 'center', 'line-height': 1.3
      } },
      { selector: 'node.split', style: { 'shape': 'hexagon', 'width': 150, 'height': 66 } },
      { selector: 'node.sel', style: { 'border-width': 4, 'border-color': '#ffffff' } },
      { selector: 'edge', style: {
        'width': 2, 'line-color': '#2c3345', 'target-arrow-color': '#2c3345',
        'target-arrow-shape': 'triangle', 'curve-style': 'bezier', 'arrow-scale': 1.1
      } },
      { selector: 'edge.flow', style: { 'line-color': '#5b8bff', 'target-arrow-color': '#5b8bff', 'width': 3, 'line-style': 'dashed' } }
    ];
  };

  /* ---------- painting ---------- */
  ExecutionGraph.prototype.label = function (id) {
    var s = this.state[id];
    var node = null;
    for (var i = 0; i < this.nodes.length; i++) { if (this.nodes[i].id === id) { node = this.nodes[i]; break; } }
    var name = (node && node.name) || (s && s.name) || id;
    if (!s) { return name; }
    var a = s.async > 0 ? '  ⏱' + s.async : '';
    return name + '\n▸ ' + (s.in || 0) + '   ◂ ' + (s.out || 0) + a + '\n' + fmtTime(s.ms || 0);
  };

  ExecutionGraph.prototype.stateColor = function (id) {
    var s = this.state[id];
    return STATE_COLORS[(s && s.state) || 'Waiting'] || STATE_COLORS.Waiting;
  };

  ExecutionGraph.prototype.paint = function () {
    if (!this.cy) { return; }
    var self = this, active = 0, asyncTotal = 0, totalIn = 0, totalOut = 0;
    this.cy.batch(function () {
      self.nodes.forEach(function (n, idx) {
        var node = self.cy.getElementById(n.id);
        node.data('label', self.label(n.id));
        node.data('border', self.stateColor(n.id));
        var s = self.state[n.id];
        if (s && (s.state === 'Running' || s.state === 'Async')) { active++; }
        if (s) { asyncTotal += s.async || 0; }
        if (idx === 0 && s) { totalIn = s.in || 0; }
        if (idx === self.nodes.length - 1 && s) { totalOut = s.out || 0; }
      });
      self.cy.edges().forEach(function (e) {
        var s = self.state[e.source().id()];
        e.toggleClass('flow', !self.finished && !!s && (s.state === 'Running' || s.state === 'Async'));
      });
    });
    this.kpiEl.innerHTML =
      kpi('in', totalIn) + kpi('out', totalOut) + kpi('active', active + '/' + this.nodes.length) + kpi('async', asyncTotal);
    if (this.selected) { this.renderDrawer(); }
  };

  function kpi(k, v) { return '<span class="eg-kpi"><b>' + v + '</b>' + k + '</span>'; }

  /* ---------- drawer ---------- */
  ExecutionGraph.prototype.select = function (id) {
    this.selected = id;
    this.cy.nodes().removeClass('sel');
    this.cy.getElementById(id).addClass('sel');
    this.renderDrawer();
  };

  ExecutionGraph.prototype.renderDrawer = function () {
    var id = this.selected, s = this.state[id] || {};
    var node = null;
    for (var i = 0; i < this.nodes.length; i++) { if (this.nodes[i].id === id) { node = this.nodes[i]; break; } }
    var name = (node && node.name) || s.name || id;
    var pct = s.in ? Math.round((s.out || 0) / s.in * 100) : 0;
    var tput = s.ms > 0 ? ((s.out || 0) / (s.ms / 1000)) : 0;
    var stateName = s.state || 'Waiting';
    var stepLogs = this.logLines.filter(function (l) { return l.indexOf(name) !== -1; }).slice(-30);
    this.drawer.innerHTML =
      '<div class="eg-d-head"><div class="eg-d-name">' + name + '</div><div class="eg-d-type">' + ((node && node.type) || '') + '</div></div>' +
      '<div class="eg-d-state">State: <b style="color:' + (STATE_COLORS[stateName] || '#aaa') + '">' + stateName + '</b>' +
      '<div class="eg-progress"><span style="width:' + pct + '%"></span></div></div>' +
      '<div class="eg-stats">' +
        stat('items in', s.in || 0) + stat('items out', s.out || 0) +
        stat('time', fmtTime(s.ms || 0)) + stat('throughput', tput.toFixed(0) + '/s') +
        stat('async', s.async || 0) + stat('buffered', (s.in || 0) - (s.out || 0)) +
      '</div>' +
      '<div class="eg-d-sub">Step logs</div>' +
      '<pre class="eg-d-logs">' + (stepLogs.length ? stepLogs.map(esc).join('\n') : '<span class="eg-muted">no logs matched this step</span>') + '</pre>';
  };

  function stat(k, v) { return '<div class="eg-stat"><span class="k">' + k + '</span><span class="v">' + v + '</span></div>'; }
  function esc(s) { return String(s).replace(/[&<>]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;' }[c]; }); }

  /* ---------- status badge ---------- */
  ExecutionGraph.prototype.setStatus = function (status, note) {
    status = (status || 'waiting').toLowerCase();
    this.cfg.status = status;
    this.finished = !!TERMINAL[status];
    var cls = 'eg-badge eg-' + status;
    this.badgeEl.className = cls;
    this.badgeEl.innerHTML = '<span class="eg-dot"></span> ' + status.toUpperCase() + (note ? ' · ' + note : '');
  };

  /* ---------- live: polling ---------- */
  ExecutionGraph.prototype.startPolling = function () {
    this.connEl.innerHTML = '<span class="eg-dot"></span> polling';
    var self = this;
    var tick = function () {
      if (self.paused) { return; }
      fetch(self.cfg.stateUrl, { headers: { Accept: 'application/json' } })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          self.state = data.state || self.state;
          self.setStatus(data.status || self.cfg.status);
          self.paint();
          if (data.finished) { self.stopTimers(); self.connEl.innerHTML = ''; self.fetchLogsOnce(); }
        })
        .catch(function () { /* transient */ });
    };
    this.timers.push(setInterval(tick, this.cfg.pollInterval));
  };

  /* ---------- live: Mercure (SSE) ---------- */
  ExecutionGraph.prototype.startMercure = function () {
    var self = this;
    var url = this.cfg.mercureUrl + (this.cfg.mercureUrl.indexOf('?') === -1 ? '?' : '&') + 'topic=' + encodeURIComponent(this.cfg.mercureTopic);
    try {
      var es = new EventSource(url, { withCredentials: true });
      this.connEl.innerHTML = '<span class="eg-dot eg-live"></span> live · Mercure';
      es.onmessage = function (evt) {
        try {
          var msg = JSON.parse(evt.data);
          if (msg.state) { self.state = msg.state; }
          if (msg.log) { self.pushLog(msg.log); }
          if (msg.status) { self.setStatus(msg.status); }
          self.paint();
          if (msg.finished) { es.close(); self.connEl.innerHTML = ''; }
        } catch (e) { /* ignore malformed frame */ }
      };
      es.onerror = function () { es.close(); self.connEl.innerHTML = ''; self.startPolling(); };
      this.es = es;
    } catch (e) {
      this.startPolling();
    }
  };

  /* ---------- logs ---------- */
  ExecutionGraph.prototype.startLogs = function () {
    this.fetchLogsOnce();
    if (this.finished) { return; }
    var self = this;
    this.timers.push(setInterval(function () { if (!self.paused) { self.fetchLogsOnce(); } }, Math.max(1500, this.cfg.pollInterval)));
  };

  ExecutionGraph.prototype.fetchLogsOnce = function () {
    var self = this;
    fetch(this.cfg.logsUrl + (this.cfg.logsUrl.indexOf('?') === -1 ? '?' : '&') + 'offset=' + this.logOffset, { headers: { Accept: 'application/json' } })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        (data.lines || []).forEach(function (l) { self.pushLog(l); });
        if (typeof data.offset === 'number') { self.logOffset = data.offset; }
      })
      .catch(function () { /* transient */ });
  };

  ExecutionGraph.prototype.pushLog = function (line) {
    this.logLines.push(line);
    if (this.logLines.length > 1000) { this.logLines.shift(); }
    var lvl = /\.(ERROR|CRITICAL|ALERT|EMERGENCY)/.test(line) ? 'err' : (/\.(WARNING)/.test(line) ? 'warn' : 'info');
    this.log(lvl, line, true);
    if (this.selected) { this.renderDrawer(); }
  };

  ExecutionGraph.prototype.log = function (lvl, msg, raw) {
    var row = el('div', 'eg-ln eg-' + lvl, esc(msg));
    this.feedEl.appendChild(row);
    while (this.feedEl.children.length > 500) { this.feedEl.removeChild(this.feedEl.firstChild); }
    this.feedEl.scrollTop = this.feedEl.scrollHeight;
  };

  ExecutionGraph.prototype.relayout = function () {
    if (this.cy && this.nodes.length) {
      this.cy.layout({ name: 'dagre', rankDir: 'TB', nodeSep: 45, rankSep: 60, animate: true, animationDuration: 350 }).run();
    }
  };

  ExecutionGraph.prototype.stopTimers = function () {
    this.timers.forEach(clearInterval);
    this.timers = [];
  };

  /* ---------- boot ---------- */
  function boot() {
    var roots = document.querySelectorAll('[data-etl-graph]');
    for (var i = 0; i < roots.length; i++) {
      if (!roots[i].__etlGraph) { roots[i].__etlGraph = new ExecutionGraph(roots[i]); }
    }
  }
  if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', boot); }
  else { boot(); }
  window.Oliverde8EtlGraph = { boot: boot };
})();
