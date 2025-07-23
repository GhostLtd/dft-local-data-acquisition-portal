<?php

namespace App\Form\Type\Admin;

use App\Entity\FundReturn\CrstsFundReturn;
use App\Entity\FundReturn\FundReturn;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonGroupType;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DataExportType extends AbstractType
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
    ) {}

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $yearsAndQuarters = $this->entityManager
            ->createQueryBuilder()
            ->from(CrstsFundReturn::class, 'cfr')
            ->distinct()
            ->select('cfr.year, cfr.quarter')
            ->where('cfr.state = :submitted')
            ->orderBy('cfr.year', 'DESC')
            ->addOrderBy('cfr.quarter', 'DESC')
            ->setParameter('submitted', FundReturn::STATE_SUBMITTED)
            ->getQuery()
            ->getArrayResult();

        $choices = [];
        foreach($yearsAndQuarters as $yearAndQuarter) {
            ['year' => $year, 'quarter' => $quarter] = $yearAndQuarter;
            $nextYear = substr(strval($year + 1), -2);
            $choices["{$year}/{$nextYear} Q{$quarter}"] = "{$year}-{$quarter}";
        }

        $builder
            ->add('year_and_quarter', Gds\ChoiceType::class, [
                'label' => 'forms.data_export.year_and_quarter.label',
                'placeholder' => 'forms.data_export.year_and_quarter.placeholder',
                'choices' => $choices,
                'expanded' => false,
            ])
            ->add('buttons', ButtonGroupType::class, [
                'priority' => -1000,
            ]);

        $builder->get('buttons')
            ->add('confirm', ButtonType::class, [
                'type' => 'submit',
                'label' => 'forms.data_export.confirm.label',
                'translation_domain' => 'messages',
                'prevent_double_click' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {}
}
