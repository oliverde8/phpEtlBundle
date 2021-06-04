<?php

namespace Oliverde8\PhpEtlBundle\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Oliverde8\PhpEtlBundle\Entity\EtlExecution;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use Oliverde8\PhpEtlBundle\Security\EtlExecutionVoter;
use Oliverde8\PhpEtlBundle\Services\ChainProcessorsManager;
use Oliverde8\PhpEtlBundle\Services\ChainWorkDirManager;

class EtlExecutionCrudController extends AbstractCrudController
{
    /** @var ChainWorkDirManager */
    protected $chainWorkDirManager;

    /** @var ChainProcessorsManager */
    protected $chainProcessorManager;

    /** @var AdminUrlGenerator */
    protected $adminUrlGenerator;

    /**
     * EtlExecutionCrudController constructor.
     * @param ChainWorkDirManager $chainWorkDirManager
     * @param ChainProcessorsManager $chainProcessorManager
     * @param AdminUrlGenerator $adminUrlGenerator
     */
    public function __construct(
        ChainWorkDirManager $chainWorkDirManager,
        ChainProcessorsManager $chainProcessorManager,
        AdminUrlGenerator $adminUrlGenerator
    ) {
        $this->chainWorkDirManager = $chainWorkDirManager;
        $this->chainProcessorManager = $chainProcessorManager;
        $this->adminUrlGenerator = $adminUrlGenerator;
    }

    public static function getEntityFqcn(): string
    {
        return EtlExecution::class;
    }

    public function configureActions(Actions $actions): Actions
    {

        $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->remove(Crud::PAGE_INDEX, Action::EDIT)
            ->remove(Crud::PAGE_INDEX, Action::DELETE)
            ->remove(Crud::PAGE_DETAIL, Action::EDIT)
            ->remove(Crud::PAGE_DETAIL, Action::DELETE);

        if (!$this->isGranted(EtlExecutionVoter::QUEUE, EtlExecution::class)) {
            $actions->remove(Crud::PAGE_INDEX, Action::NEW);
        }
        if (!$this->isGranted(EtlExecutionVoter::VIEW, EtlExecution::class)) {
            $actions->remove(Crud::PAGE_INDEX, Action::DETAIL);
        }

        return $actions;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle("index", "Etl Executions")
            ->setDateTimeFormat('dd/MM/y - HH:mm:ss')
            ->setSearchFields(["name", "id"])
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        if (Crud::PAGE_DETAIL === $pageName) {
            return [
                FormField::addPanel("Details")->addCssClass("col-12 col-xl-6"),
                Field::new('name'),
                Field::new('username'),
                TextField::new('status')->setTemplatePath('@Oliverde8PhpEtl/fields/status.html.twig'),
                FormField::addPanel()->addCssClass("col-12 col-xl-6"),
                Field::new('createTime'),
                Field::new('startTime'),
                Field::new('endTime'),
                Field::new('failTime'),

                FormField::addPanel('Execution Inputs')->addCssClass('col-12'),
                CodeEditorField::new('inputData')->setTemplatePath('@Oliverde8PhpEtl/fields/code_editor.html.twig')->addCssClass('etl-json-div'),
                CodeEditorField::new('inputOptions')->setTemplatePath('@Oliverde8PhpEtl/fields/code_editor.html.twig')->addCssClass('etl-json-div'),
                CodeEditorField::new('definition')->setTemplatePath('@Oliverde8PhpEtl/fields/code_editor.html.twig'),

                FormField::addPanel('Execution outpus')->addCssClass("col-12"),
                TextField::new('Files')->formatValue(function ($value, EtlExecution $entity) {
                    $urls = [];
                    if ($this->isGranted(EtlExecutionVoter::DOWNLOAD, EtlExecution::class)) {
                        $files = $this->chainWorkDirManager->listFiles($entity);
                        foreach ($files as $file) {
                            $url = $this->adminUrlGenerator
                                ->setRoute("etl_execution_download_file", ['execution' => $entity->getId(), 'filename' => $file])
                                ->generateUrl();

                            $urls[$url] = $file;
                        }
                    }

                    return $urls;
                })->setTemplatePath('@Oliverde8PhpEtl/fields/files.html.twig'),
                CodeEditorField::new('errorMessage')->setTemplatePath('@Oliverde8PhpEtl/fields/code_editor.html.twig'),
                TextField::new('Logs')->formatValue(function ($value, EtlExecution $entity) {
                    $logs = $this->chainWorkDirManager->getFirstLogLines($entity, 100);
                    $url = "";
                    $moreLogs = false;
                    if (!empty($logs)) {
                        $url = $this->adminUrlGenerator
                            ->setRoute("etl_execution_download_file", ['execution' => $entity->getId(), 'filename' => 'execution.log'])
                            ->generateUrl();
                    }
                    if (count($logs) > 100) {
                        $moreLogs = true;
                    }

                    return [
                        "lines" => $logs,
                        'downloadUrl' => $url,
                        'moreLogs' => $moreLogs,
                    ];
                })->setTemplatePath('@Oliverde8PhpEtl/fields/logs.html.twig'),

            ];
        }
        if (Crud::PAGE_INDEX === $pageName) {
            return [
                Field::new('id'),
                Field::new('name'),
                Field::new('username'),
                TextField::new('status')->setTemplatePath('@Oliverde8PhpEtl/fields/status.html.twig'),
                Field::new('createTime'),
                Field::new('startTime'),
                Field::new('endTime'),
            ];
        }
        if (Crud::PAGE_NEW === $pageName) {
            return [
                ChoiceField::new('name', 'Chain Name')
                    ->setChoices($this->getChainOptions()),
                CodeEditorField::new('inputData')->setCssClass("etl-json-input"),
                CodeEditorField::new('inputOptions')->setCssClass("etl-json-input"),
            ];
        }

        return parent::configureFields($pageName);
    }

    public function configureAssets(Assets $assets): Assets
    {
        return $assets
            ->addJsFile('https://cdnjs.cloudflare.com/ajax/libs/jsoneditor/9.4.1/jsoneditor.min.js')
            ->addCssFile('https://cdnjs.cloudflare.com/ajax/libs/jsoneditor/9.4.1/jsoneditor.min.css')
            ->addJsFile('/bundles/oliverde8phpetl/admin/fields/json-editor.js');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('name')
            ->add('username')
            ->add(
                ChoiceFilter::new('status')->setChoices([
                    EtlExecution::STATUS_WAITING => EtlExecution::STATUS_WAITING,
                    EtlExecution::STATUS_RUNNING => EtlExecution::STATUS_RUNNING,
                    EtlExecution::STATUS_SUCCESS => EtlExecution::STATUS_SUCCESS,
                    EtlExecution::STATUS_FAILURE => EtlExecution::STATUS_FAILURE,
                ])->canSelectMultiple()
            )
            ->add('startTime')
            ->add('endTime');
    }

    public function createEntity(string $entityFqcn)
    {
        $user = $this->getUser();
        $username = null;
        if ($user) {
            $username = $user->getUsername();
        }

        $execution = new EtlExecution("", "", [], []);
        $execution->setUsername($username);
        return $execution;
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $entityManager->persist($entityInstance);
        $entityManager->flush();
    }

    protected function getChainOptions()
    {
        $options = [];
        foreach (array_keys($this->chainProcessorManager->getDefinitions()) as $definitionName) {
            $options[$definitionName] = $definitionName;
        }

        return $options;
    }
}
