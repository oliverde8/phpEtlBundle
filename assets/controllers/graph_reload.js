import { Controller } from '@hotwired/stimulus';
import mermaid from 'mermaid';

export default class extends Controller {
  static values = { continueUpdate: Boolean, refreshInterval: Number, apiUrl: String }

  async connect() {
    console.log(this.continueUpdateValue);
    console.log(this.refreshIntervalValue);
    console.log(this.apiUrlValue);
    // console.log(mermaid);

    mermaid.initialize({startOnLoad: false});
    this.mermaid = await mermaid.run({
      nodes: [this.element],
    });

    if (this.continueUpdateValue) {
      this.startTimer(this.refreshIntervalValue);
    }
  }

  startTimer(timer) {
    let that = this;
    setTimeout(function () {
      fetch(that.apiUrlValue, {method: "get"})
        .then(response => {
          return response.json();
        }).then(async data => {
        if (data.continueUpdate === 'false') {
          location.reload()
          return;
        }

        console.log(data);
        const {svg, bindFunctions} = await mermaid.render('run-graph', data.graph);
        that.element.innerHTML = svg;

        that.startTimer(data.refreshInterval);
      })
    }, timer * 1000);
  }
}
