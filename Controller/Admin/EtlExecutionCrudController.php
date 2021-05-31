<?php

namespace Oliverde8\PhpEtlBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use Oliverde8\PhpEtlBundle\Entity\EtlExecution;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use Oliverde8\PhpEtlBundle\Services\ChainWorkDirManager;

class EtlExecutionCrudController extends AbstractCrudController
{
    /** @var ChainWorkDirManager */
    protected $chainWorkDirManager;

    /**
     * EtlExecutionCrudController constructor.
     * @param ChainWorkDirManager $chainWorkDirManager
     */
    public function __construct(ChainWorkDirManager $chainWorkDirManager)
    {
        $this->chainWorkDirManager = $chainWorkDirManager;
    }


    public static function getEntityFqcn(): string
    {
        return EtlExecution::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->remove(Crud::PAGE_INDEX, Action::NEW)
            ->remove(Crud::PAGE_INDEX, Action::EDIT)
            ->remove(Crud::PAGE_INDEX, Action::DELETE)

            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->remove(Crud::PAGE_DETAIL, Action::EDIT)
            ->remove(Crud::PAGE_DETAIL, Action::DELETE);
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle("index", "Etl Executions")
            ->setDateTimeFormat('dd/MM/y - HH:mm:ss')
            ->setSearchFields(["name", "id"])
            ->setDefaultSort(['startTime' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        if (Crud::PAGE_DETAIL === $pageName) {
            return [
                Field::new('name'),
                Field::new('status'),
                Field::new('startTime'),
                Field::new('endTime'),
                Field::new('failTime'),
                TextField::new('Files')->formatValue(function ($value, EtlExecution $entity) {
                    // TODO create url's.
                    return $this->chainWorkDirManager->listFiles($entity);
                })->setTemplatePath('@Oliverde8PhpEtl/fields/files.html.twig'),
                CodeEditorField::new('inputData')->setTemplatePath('@Oliverde8PhpEtl/fields/code_editor.html.twig'),
                CodeEditorField::new('inputOptions')->setTemplatePath('@Oliverde8PhpEtl/fields/code_editor.html.twig'),
                CodeEditorField::new('definition')->setTemplatePath('@Oliverde8PhpEtl/fields/code_editor.html.twig'),
                CodeEditorField::new('errorMessage')->setTemplatePath('@Oliverde8PhpEtl/fields/code_editor.html.twig'),
            ];
        }
        if (Crud::PAGE_INDEX === $pageName) {
            return [
                Field::new('id'),
                Field::new('name'),
                TextField::new('status')->setTemplatePath('@Oliverde8PhpEtl/fields/status.html.twig'),
                Field::new('startTime'),
                Field::new('endTime'),
            ];
        }

        return parent::configureFields($pageName);
    }


    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('name')
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
}
