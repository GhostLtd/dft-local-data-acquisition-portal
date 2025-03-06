<?php

namespace App\Form\Type\SchemeReturn\Crsts;

use App\Entity\Enum\FundedMostlyAs;
use App\Entity\Enum\MilestoneType;
use App\Entity\Milestone;
use App\Entity\SchemeReturn\CrstsSchemeReturn;
use App\Form\Type\BaseButtonsFormType;
use Ghost\GovUkFrontendBundle\Form\Type\BooleanChoiceType;
use Ghost\GovUkFrontendBundle\Form\Type\DateType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Event\PreSetDataEvent;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

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

        $fundedMostlyAs = $data->getScheme()->getCrstsData()->getFundedMostlyAs();
        $relevantMilestoneEnums = $this->getRelevantMilestoneEnums($data);

        $form
            ->add('developmentOnly', BooleanChoiceType::class, [
                'label' => 'forms.scheme.milestone_dates.is_development_only.label',
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'help' => 'forms.scheme.milestone_dates.is_development_only.help',
                'choice_options' => [
                    'boolean.false' => [
                        'conditional_form_name' => 'nonDevelopmentalMilestones',
                    ]
                ],
            ])
            ->add('nonDevelopmentalMilestones', FormType::class, [
                'label' => false,
                'inherit_data' => true,
            ]);

        foreach($relevantMilestoneEnums as $milestoneType) {
            $fieldKey = $milestoneType->value;

            $parent = $milestoneType->isDevelopmentMilestone() ? $form : $form->get('nonDevelopmentalMilestones');

            $parent->add($milestoneType->value, DateType::class, [
                'label' => "forms.scheme.milestone_dates.milestones.{$fieldKey}.label",
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'label_translation_parameters' => ['funded_mostly_as' => $fundedMostlyAs->value],
                'help' => "forms.scheme.milestone_dates.milestones.{$fieldKey}.help",
                'priority' => 1,
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
        $isDevelopmentOnly = $viewData->getDevelopmentOnly();
        $forms['developmentOnly']->setData($isDevelopmentOnly);

        foreach($milestoneEnums as $milestoneEnum) {
            $data = $this->getMilestone($viewData, $milestoneEnum)?->getDate();

            if (!$isDevelopmentOnly || $milestoneEnum->isDevelopmentMilestone()) {
                $forms[$milestoneEnum->value]->setData($data);
            }
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
        $isDevelopmentOnly = $forms['developmentOnly']->getData();

        $viewData->setDevelopmentOnly($isDevelopmentOnly);

        foreach($milestoneEnums as $milestoneEnum) {
            $value = $forms[$milestoneEnum->value]->getData();

            $milestone = $this->getMilestone($viewData, $milestoneEnum);
            $shouldBeRemoved = $isDevelopmentOnly && !$milestoneEnum->isDevelopmentMilestone();

            if ($shouldBeRemoved) {
                if ($milestone) {
                    $viewData->removeMilestone($milestone);
                }
            } else {
                if (!$milestone) {
                    $milestone = (new Milestone())->setType($milestoneEnum);
                    $viewData->addMilestone($milestone);
                }

                $milestone->setDate($value);
            }
        }
    }

    protected function getRelevantMilestoneEnums(CrstsSchemeReturn $schemeReturn): array
    {
        $isCDEL = $schemeReturn->getScheme()->getCrstsData()->getFundedMostlyAs() === FundedMostlyAs::CDEL;

        return array_filter(
            MilestoneType::cases(),
            fn(MilestoneType $e) => $isCDEL || $e !== MilestoneType::FINAL_DELIVERY
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
