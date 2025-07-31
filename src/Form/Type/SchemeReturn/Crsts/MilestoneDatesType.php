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

            $groupName = "group_{$milestoneType->value}";
            $parent->add($groupName, MilestoneGroupType::class);
            $group = $parent->get($groupName);

            $group->add($milestoneType->value, DateType::class, [
                'label' => "forms.scheme.milestone_dates.milestones.{$fieldKey}.label",
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
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
                'milestones_enabled' => true,
                'milestones_editable' => false,
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
            $data = $viewData->getMilestoneByType($milestoneEnum)?->getDate();

            if (!$isDevelopmentOnly || $milestoneEnum->isDevelopmentMilestone()) {
                $groupName = "group_{$milestoneEnum->value}";
                $forms[$groupName][$milestoneEnum->value]->setData($data);
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

        $isDevelopmentOnly = $forms['developmentOnly']->getData();

        $viewData->setDevelopmentOnly($isDevelopmentOnly);
        $isCDEL = $this->isCDEL($viewData);

        // For all non-baseline milestones (i.e. do not touch baseline milestones data)
        foreach(MilestoneType::getNonBaselineCases() as $milestoneType) {
            $milestone = $viewData->getMilestoneByType($milestoneType);

            $shouldBeRemoved =
                // If CDEL, remove non-CDEL milestones
                // If RDEL, remove non-RDEL milestones
                !($isCDEL ? $milestoneType->isCDEL() : $milestoneType->isRDEL())
                // If development_only ticked, remove non-development milestones
                || ($isDevelopmentOnly && !$milestoneType->isDevelopmentMilestone());

            if ($shouldBeRemoved) {
                if ($milestone) {
                    $viewData->removeMilestone($milestone);
                }
                continue;
            }

            $groupName = "group_{$milestoneType->value}";
            $value = $forms[$groupName][$milestoneType->value]->getData();

            if (!$milestone) {
                $milestone = (new Milestone())->setType($milestoneType);
                $viewData->addMilestone($milestone);
            }

            $milestone->setDate($value);
        }
    }

    protected function getRelevantMilestoneEnums(CrstsSchemeReturn $schemeReturn): array
    {
        return MilestoneType::getNonBaselineCases($this->isCDEL($schemeReturn));
    }

    protected function isCDEL(CrstsSchemeReturn $schemeReturn): bool
    {
        return $schemeReturn->getScheme()->getCrstsData()->getFundedMostlyAs() === FundedMostlyAs::CDEL;
    }
}
