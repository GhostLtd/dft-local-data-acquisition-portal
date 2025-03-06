<?php

namespace App\Form\Type\SchemeReturn\Crsts;

use App\Entity\Enum\FundedMostlyAs;
use App\Entity\Enum\MilestoneType;
use App\Entity\Enum\MilestoneType as MilestoneEnum;
use App\Entity\Milestone;
use App\Entity\SchemeReturn\CrstsSchemeReturn;
use App\Form\Type\BaseButtonsFormType;
use Ghost\GovUkFrontendBundle\Form\Type\BooleanChoiceType;
use Ghost\GovUkFrontendBundle\Form\Type\DateType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Event\PreSetDataEvent;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MilestoneDatesType extends AbstractType implements DataMapperInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->setDataMapper($this)
            ->addEventListener(FormEvents::PRE_SET_DATA, $this->buildMilestoneFormElements(...));
    }

    public function buildMilestoneFormElements(PreSetDataEvent $event): void
    {
        $data = $event->getData();
        $form = $event->getForm();

        if (!$data instanceof CrstsSchemeReturn) {
            throw new UnexpectedTypeException($data, CrstsSchemeReturn::class);
        }

        $fundedMostlyAs = $data->getSchemeFund()->getFundedMostlyAs();

        $form
            ->add('isDevelopmentOnly', BooleanChoiceType::class, [
                'label' => 'forms.scheme.milestone_dates.is_development_only.label',
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'help' => 'forms.scheme.milestone_dates.is_development_only.help',
                'choice_options' => [
                    'boolean.true' => [
                        'conditional_form_name' => 'activeTravelElement',
                    ]
                ],
            ]);

        foreach($this->getRelevantMilestoneEnums($data) as $milestoneType) {
            $fieldKey = $milestoneType->value;

            $form->add($milestoneType->value, DateType::class, [
                'label' => "forms.scheme.milestone_dates.milestones.{$fieldKey}.label",
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'label_translation_parameters' => ['funded_mostly_as' => $fundedMostlyAs->value],
                'help' => "forms.scheme.milestone_dates.milestones.{$fieldKey}.help",
            ]);
        }
    }

    public function getParent(): string
    {
        return BaseButtonsFormType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'data_class' => CrstsSchemeReturn::class,
                'validation_groups' => 'milestone_dates',
            ]);
    }

    public function mapDataToForms(mixed $viewData, \Traversable $forms): void
    {
        if (!$viewData instanceof CrstsSchemeReturn) {
            throw new UnexpectedTypeException($viewData, CrstsSchemeReturn::class);
        }

        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        $milestoneEnums = $this->getRelevantMilestoneEnums($viewData);

        foreach($milestoneEnums as $milestoneEnum) {
            $data = $this->getMilestone($viewData, $milestoneEnum)?->getDate();
            $forms[$milestoneEnum->value]->setData($data);
        }
    }

    public function mapFormsToData(\Traversable $forms, mixed &$viewData): void
    {
        if (!$viewData instanceof CrstsSchemeReturn) {
            throw new UnexpectedTypeException($viewData, CrstsSchemeReturn::class);
        }

        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        $milestoneEnums = $this->getRelevantMilestoneEnums($viewData);

        foreach($milestoneEnums as $milestoneEnum) {
            $value = $forms[$milestoneEnum->value]->getData();

            $milestone = $this->getMilestone($viewData, $milestoneEnum);

            if (!$milestone) {
                $milestone = (new Milestone())->setType($milestoneEnum);
                $viewData->addMilestone($milestone);
            }

            $milestone->setDate($value);
        }
    }

    protected function getRelevantMilestoneEnums(CrstsSchemeReturn $schemeReturn): array
    {
        $isCDEL = $schemeReturn->getSchemeFund()->getFundedMostlyAs() === FundedMostlyAs::CDEL;

        return array_filter(
            MilestoneEnum::cases(),
            fn(MilestoneEnum $e) => $isCDEL || $e !== MilestoneType::FINAL_DELIVERY
        );
    }

    public function getMilestone(CrstsSchemeReturn $crstsSchemeReturn, MilestoneType $milestoneEnum): ?Milestone
    {
        foreach($crstsSchemeReturn->getMilestones() as $milestone) {
            if ($milestone->getType() === $milestoneEnum) {
                return $milestone;
            }
        }

        return null;
    }
}
