<?php

namespace App\Form\Type;

use App\Entity\Authority;
use App\Entity\Enum\Permission;
use Ghost\GovUkFrontendBundle\Form\Type\ChoiceType;
use Symfony\Component\Form\Event\PreSetDataEvent;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;

class UserType extends BaseUserType
{
    public function __construct(protected UserDataMapper $userDataMapper)
    {}


    // Essentially we want the fields from BaseUserType, albeit with the buttons
    // This should work fine, as long as BaseUserType never has a parent
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $authority = $options['authority'];
        $this->userDataMapper->setAuthority($authority);

        $builder
            ->setDataMapper($this->userDataMapper)
            ->addEventListener(FormEvents::PRE_SET_DATA, function (PreSetDataEvent $event) use ($authority) {
                $form = $event->getForm();
                $user = $event->getData();

                $isAdmin = $user === $authority->getAdmin();

                if (!$isAdmin) {
                    $form->add('permission', ChoiceType::class, [
                        'label' => "forms.user.permission.label",
                        'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                        'help' => "forms.user.permission.help",
                        'choices' => Permission::cases(),
                        'choice_label' => fn(Permission $choice) => "enum.permission.{$choice->value}",
                        'choice_value' => fn(?Permission $choice) => $choice?->value,
                        'expanded' => false,
                        'placeholder' => 'forms.generic.placeholder',
                        'constraints' => [
                            new NotNull(message: 'user.permission.not_null', groups: ['user.edit']),
                        ],
                    ]);
                }
            });
    }

    public function getParent(): ?string
    {
        return BaseButtonsFormType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver
            ->setDefault('validation_groups', ['user.edit'])
            ->setRequired('authority')
            ->setAllowedTypes('authority', [Authority::class]);
    }
}
