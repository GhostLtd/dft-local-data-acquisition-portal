<?php

namespace App\Form\Type\Admin;

use App\Entity\Authority;
use App\Entity\Enum\Fund;
use App\Entity\User;
use App\Form\Type\BaseButtonsFormType;
use App\Form\Type\BaseUserType;
use App\Repository\UserRepository;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Ghost\GovUkFrontendBundle\Form\Type\ChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class AuthorityType extends AbstractType
{

    public const string ADMIN_CHOICE_EXISTING = 'existing';
    public const string ADMIN_CHOICE_NEW = 'new';

    public function __construct(protected AuthorityDataMapper $authorityDataMapper)
    {}

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->setDataMapper($this->authorityDataMapper)

            ->add('name', Gds\InputType::class, [
                'label' => 'authority.form.name',
                'label_attr' => ['class' => 'govuk-label--m'],
            ])
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
                /** @var Authority $data */
                $data = $event->getData();
                $form = $event->getForm();

                if ($data->getId()) {
                    $form
                        ->add('admin_choice', Gds\ChoiceType::class, [
                            'label' => 'authority.form.admin',
                            'label_attr' => ['class' => 'govuk-fieldset__legend--m'],
                            'mapped' => false,
                            'choices' => [
                                'authority.form.admin-edit.existing' => self::ADMIN_CHOICE_EXISTING,
                                'authority.form.admin-edit.new' => self::ADMIN_CHOICE_NEW,
                            ],
                            'choice_options' => [
                                'authority.form.admin-edit.existing' => [
                                    'conditional_form_name' => 'existing_admin',
                                    'label_attr' => ['class' => 'govuk-label--s'],
                                ],
                                'authority.form.admin-edit.new' => [
                                    'conditional_form_name' => 'admin',
                                    'label_attr' => ['class' => 'govuk-label--s'],
                                ],
                            ]
                        ])
                        ->add('existing_admin', Gds\EntityType::class, [
                            'mapped' => false,
                            'class' => User::class,
                            'query_builder' => function (UserRepository $er) use ($data) {return $er->getAllForAuthorityQueryBuilder($data);},
                            'choice_label' => 'name',
                            'label' => 'authority.form.admin',
                            'label_attr' => ['class' => 'govuk-visually-hidden'],
                            'expanded' => false,
                        ])
                        ->add('admin', BaseUserType::class, [
                            'label' => 'authority.form.admin',
                            'label_attr' => ['class' => 'govuk-visually-hidden'],
                        ])
                    ;
                } else {
                    $funds = array_map(fn(Fund $f) => $f->value, Fund::enabledCases());

                    $form
                        ->add('admin', BaseUserType::class, [
                            'label' => 'authority.form.admin',
                            'label_attr' => ['class' => 'govuk-label--m'],
                        ])
                        ->add('funds', ChoiceType::class, [
                            'choices' => array_combine($funds, $funds),
                            'choice_label' => fn(string $choice) => new TranslatableMessage("enum.fund.{$choice}", [], 'messages'),
                            'multiple' => true,
                            'label_attr' => ['class' => 'govuk-fieldset__legend--m'],
                        ]);
                }
            })
            ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Authority::class,
            'translation_domain' => 'admin',
            'validation_groups' => function(Form $form) {
                $groups = ['authority'];
                $groups[] = match(true) {
                    ($form->has('admin_choice')
                            && $form->get('admin_choice')->getData() === self::ADMIN_CHOICE_NEW)
                            || $form->getData()->getId() === null
                        => 'authority.new_admin',
                    default => 'authority.existing_admin',
                };
                return $groups;
            },
        ]);
    }

    public function getParent(): string
    {
        return BaseButtonsFormType::class;
    }
}
