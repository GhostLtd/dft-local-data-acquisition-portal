<?php

namespace App\Form\Type\SchemeReturn\Crsts;

use App\Entity\SchemeReturn\CrstsSchemeReturn;
use App\Form\Type\BaseButtonsFormType;
use Ghost\GovUkFrontendBundle\Form\Type\InputType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OverallFundingType extends AbstractType implements DataMapperInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->setDataMapper($this)
            ->add('totalCost', InputType::class, [
                'label' => 'forms.crsts.overall_funding.total_cost.label',
                'label_attr' => ['class' => 'govuk-label--s'],
                'prefix' => '£',
                'attr' => ['class' => 'govuk-input--width-10', 'data-auto-commas' => '1'],
            ])
            ->add('agreedFunding', InputType::class, [
                'label' => 'forms.crsts.overall_funding.agreed_funding.label',
                'label_attr' => ['class' => 'govuk-label--s'],
                'prefix' => '£',
                'attr' => ['class' => 'govuk-input--width-10', 'data-auto-commas' => '1'],
            ])
            ->add('benefitCostRatio', BenefitCostRatioType::class, [
                'label' => false,
                'property_path' => 'benefitCostRatio',
            ])
        ;
    }

    public function getParent(): string
    {
        return BaseButtonsFormType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CrstsSchemeReturn::class,
            'validation_groups' => ['overall_funding'],
            'error_mapping' => [
                'benefitCostRatio' => 'benefitCostRatio',
            ],
        ]);
    }

    public function mapDataToForms(mixed $viewData, \Traversable $forms): void
    {
        if (!$viewData instanceof CrstsSchemeReturn) {
            throw new UnexpectedTypeException($viewData, CrstsSchemeReturn::class);
        }

        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        $forms['agreedFunding']->setData($viewData->getAgreedFunding());
        $forms['totalCost']->setData($viewData->getTotalCost());
        $forms['benefitCostRatio']->setData($viewData->getBenefitCostRatio());
    }

    public function mapFormsToData(\Traversable $forms, mixed &$viewData): void
    {
        if (!$viewData instanceof CrstsSchemeReturn) {
            throw new UnexpectedTypeException($viewData, CrstsSchemeReturn::class);
        }

        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        $viewData->setAgreedFunding($this->removeCommas($forms['agreedFunding']->getData()));
        $viewData->setTotalCost($this->removeCommas($forms['totalCost']->getData()));
        $viewData->setBenefitCostRatio($forms['benefitCostRatio']->getData());
    }

    protected function removeCommas(?string $value): ?string
    {
        return $value ?
            str_replace(',', '', $value) :
            null;
    }
}
