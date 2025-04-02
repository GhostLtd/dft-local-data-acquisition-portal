<?php

namespace App\Form\Type;

use App\Entity\Authority;
use App\Entity\Enum\ActiveTravelElement;
use App\Entity\Enum\Fund;
use App\Entity\Enum\Role;
use App\Entity\Enum\TransportMode;
use App\Entity\Enum\TransportModeCategory;
use App\Entity\Scheme;
use Ghost\GovUkFrontendBundle\Form\Type\BooleanChoiceType;
use Ghost\GovUkFrontendBundle\Form\Type\CheckboxType;
use Ghost\GovUkFrontendBundle\Form\Type\ChoiceType;
use Ghost\GovUkFrontendBundle\Form\Type\InputType;
use Ghost\GovUkFrontendBundle\Form\Type\TextareaType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraints\IsTrue;

class SchemeType extends AbstractType implements DataMapperInterface
{
    public const string MODE_ADD = 'add';
    public const string MODE_EDIT = 'edit';


    protected Authority $authority;

    public function __construct(protected AuthorizationCheckerInterface $authorizationChecker)
    {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $data = $options['data'];
        $this->authority = $options['authority'];

        if ($data->getAuthority() === null) {
            // This is needed for the voter to work...
            $data->setAuthority($this->authority);
        }

        $canEditCriticalSchemeFields = $this->authorizationChecker->isGranted(Role::CAN_EDIT_CRITICAL_SCHEME_FIELDS, $data);
        $canEditCriticalCrstsSchemeFields = $this->authorizationChecker->isGranted(Role::CAN_EDIT_CRITICAL_CRSTS_SCHEME_FIELDS, $data);
        $canRemoveCrstsFundFromScheme = $this->authorizationChecker->isGranted(Role::CAN_REMOVE_CRSTS_FUND_FROM_SCHEME, $data);

        $transportModeCategories = TransportModeCategory::cases();

        $builder
            ->setDataMapper($this)
            ->add('name', InputType::class, [
                'label' => "forms.scheme.scheme.name.label",
                'label_attr' => ['class' => 'govuk-label--s'],
                'help' => "forms.scheme.scheme.name.help",
            ])
            ->add('description', TextareaType::class, [
                'label' => "forms.scheme.scheme.description.label",
                'label_attr' => ['class' => 'govuk-label--s'],
                'help' => "forms.scheme.scheme.description.help",
            ])
            ->add('schemeIdentifier', InputType::class, [
                'label' => "forms.scheme.scheme.scheme_identifier.label",
                'label_attr' => ['class' => 'govuk-label--s'],
                'help' => "forms.scheme.scheme.scheme_identifier.help",
                'disabled' => !$canEditCriticalSchemeFields,
            ])
            ->add('transportModeCategory', ChoiceType::class, [
                'label' => 'forms.scheme.transport_mode.category.label',
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'help' => 'forms.scheme.transport_mode.category.help',
                'choices' => $transportModeCategories,
                'choice_label' => fn(TransportModeCategory $choice) => "enum.transport_mode.categories.{$choice->value}",
                'choice_options' => function(TransportModeCategory $choice) {
                    $options = [
                        'conditional_form_name' => 'transportMode'.ucfirst($choice->value),
                    ];

                    if ($choice === TransportModeCategory::ACTIVE_TRAVEL) {
                        $options['conditional_hide_form_names'] = ['hasActiveTravelElements'];
                    }

                    return $options;
                },
                'choice_value' => fn(?TransportModeCategory $choice) => $choice->value ?? null,
            ])
            ->add('hasActiveTravelElements', BooleanChoiceType::class, [
                'label' => 'forms.scheme.transport_mode.has_active_travel_elements.label',
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'help' => 'forms.scheme.transport_mode.has_active_travel_elements.help',
                'choice_options' => [
                    'boolean.true' => [
                        'conditional_form_name' => 'activeTravelElement',
                    ]
                ],
            ])
            ->add('activeTravelElement', ChoiceType::class, [
                'label' => 'forms.scheme.transport_mode.active_travel_element.label',
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'help' => 'forms.scheme.transport_mode.active_travel_element.help',
                'choices' => ActiveTravelElement::casesExcludingNoElements(),
                'choice_label' => fn(ActiveTravelElement $choice) => "enum.active_travel_element.{$choice->value}",
                'choice_value' => fn(?ActiveTravelElement $choice) => $choice?->value,
                'expanded' => false,
                'placeholder' => 'forms.generic.placeholder',
            ])
            ->add('funds', ChoiceType::class, [
                'label' => "forms.scheme.scheme.funds.label",
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'help' => "forms.scheme.scheme.funds.help",
                'multiple' => true,
                'choices' => Fund::enabledCases(),
                'choice_label' => fn(Fund $choice) => "enum.fund.{$choice->value}",
                'choice_value' => fn(?Fund $choice) => $choice?->value,
                'choice_options' => function(?Fund $choice) use ($canRemoveCrstsFundFromScheme) {
                    $options = [];

                    if ($choice === Fund::CRSTS1) {
                        $options['conditional_form_name'] = 'crstsData';
                        $options['disabled'] = !$canRemoveCrstsFundFromScheme;
                    }

                    return $options;
                },
            ])
            ->add('crstsData', FormType::class, ['label' => false, 'inherit_data' => true])
        ;

        foreach($transportModeCategories as $category) {
            $choices = TransportMode::filterByCategory($category);

            $builder->add('transportMode'.ucfirst($category->value), ChoiceType::class, [
                'label' => "forms.scheme.transport_mode.categories.{$category->value}.label",
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'help' => "forms.scheme.transport_mode.categories.{$category->value}.help",
                'choices' => $choices,
                'choice_label' => fn(TransportMode $choice) => "enum.transport_mode.{$choice->value}",
                'choice_value' => fn(?TransportMode $choice) => $choice->value ?? null,
                'expanded' => false,
                'placeholder' => 'forms.generic.placeholder',
            ]);
        }

        $builder
            ->get('crstsData')
                ->add('retained', BooleanChoiceType::class, [
                    'label' => 'forms.scheme.scheme.crsts.is_retained.label',
                    'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                    'help' => 'forms.scheme.scheme.crsts.is_retained.help',
                    'property_path' => 'crstsData.retained',
                    'disabled' => !$canEditCriticalCrstsSchemeFields,
                ])
                ->add('previouslyTcf', BooleanChoiceType::class, [
                    'label' => 'forms.scheme.scheme.crsts.previously_tcf.label',
                    'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                    'help' => 'forms.scheme.scheme.crsts.previously_tcf.help',
                    'property_path' => 'scheme.crstsData.previouslyTcf',
                ])
        ;

        /** @var Scheme $scheme */
        $scheme = $options['data'];
        if (!$scheme?->getId()) {
            $builder->add('checklist', FormType::class, [
                'label' => 'forms.scheme.checklist.label',
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
            ]);
            $builder->get('checklist')
                ->add('dft_approved', CheckboxType::class, [
                    'label' => 'forms.scheme.dft_approved.label',
                    'help' => 'forms.scheme.dft_approved.help',
                    'constraints' => [new IsTrue(message: 'scheme.dft_approved.is_true', groups: ['scheme.add'])],
                ]);
        }
    }

    public function getParent(): ?string
    {
        return BaseButtonsFormType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver
            ->setDefault('data_class', Scheme::class)
            ->setDefault('validation_groups', $this->getValidationGroups(...))
            ->setRequired('authority')
            ->setAllowedTypes('authority', [Authority::class])
            ->setDefault('error_mapping', [
                // Note: Not sure how this makes any sense, but it doesn't map the error correctly without it!
                'crstsData.previouslyTcf' => 'crstsData.previouslyTcf',
                'crstsData.retained' => 'crstsData.retained',
                'transportMode' => 'transportModeCategory',
            ])
        ;
    }

    public function getValidationGroups(FormInterface $form): array
    {
        $addOrEdit = $form->getData()?->getId() ? self::MODE_EDIT : self::MODE_ADD;
        $schemeGroups = array_map(fn(Fund $fund) => 'scheme.'.strtolower($fund->value).'.'.$addOrEdit, $form->get('funds')->getData());

        return array_merge([
            "scheme.{$addOrEdit}",
        ], $schemeGroups);
    }

    public function mapDataToForms(mixed $viewData, \Traversable $forms): void
    {
        if (!$viewData instanceof Scheme) {
            throw new UnexpectedTypeException($viewData, Scheme::class);
        }

        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        $forms['name']->setData($viewData->getName());
        $forms['description']->setData($viewData->getDescription());
        $forms['schemeIdentifier']->setData($viewData->getSchemeIdentifier());

        $funds = $viewData->getFunds();
        $forms['funds']->setData($funds);

        if ($viewData->hasFund(Fund::CRSTS1)) {
            $data = $viewData->getCrstsData();
            $forms['retained']->setData($data->isRetained());
            $forms['previouslyTcf']->setData($data->isPreviouslyTcf());
        }

        $transportMode = $viewData?->getTransportMode();

        if ($transportMode) {
            $category = $transportMode->category();
            $forms['transportModeCategory']->setData($category);
            $forms['transportMode' . ucfirst($category->value)]->setData($transportMode);

            $activeTravelElement = $viewData->getActiveTravelElement();

            $forms['hasActiveTravelElements']->setData($activeTravelElement?->isNoActiveElement());
            $forms['activeTravelElement']->setData($activeTravelElement);
        }
    }

    public function mapFormsToData(\Traversable $forms, mixed &$viewData): void
    {
        if (!$viewData instanceof Scheme) {
            throw new UnexpectedTypeException($viewData, Scheme::class);
        }

        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        $viewData->setName($forms['name']->getData());
        $viewData->setDescription($forms['description']->getData());
        $viewData->setSchemeIdentifier($forms['schemeIdentifier']->getData());

        $viewData->setFunds($forms['funds']->getData());

        $funds = $forms['funds']->getData();
        if (in_array(Fund::CRSTS1, $funds)) {
            $crstsData = $viewData->getCrstsData();
            $crstsData->setRetained($forms['retained']->getData());
            $crstsData->setPreviouslyTcf($forms['previouslyTcf']->getData());
        }

        $category = $forms['transportModeCategory']->getData();
        $transportMode = $category ? $forms['transportMode'.ucfirst($category->value)]->getData() : null;

        if ($category === TransportModeCategory::ACTIVE_TRAVEL) {
            $activeTravelElement = null;
        } else {
            $activeTravelElement = $forms['hasActiveTravelElements']->getData();

            if ($activeTravelElement !== null) {
                $activeTravelElement = $forms['hasActiveTravelElements']->getData() ?
                    $forms['activeTravelElement']->getData() :
                    ActiveTravelElement::NO_ACTIVE_TRAVEL_ELEMENTS;
            }
        }

        $viewData
            ->setTransportMode($transportMode)
            ->setActiveTravelElement($activeTravelElement);
    }
}
