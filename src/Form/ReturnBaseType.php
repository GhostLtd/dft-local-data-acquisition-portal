<?php

namespace App\Form;

use App\Entity\Enum\CompletionStatus;
use App\Entity\SectionStatusInterface;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonGroupType;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Ghost\GovUkFrontendBundle\Form\Type\LinkType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\Workflow;
use Symfony\Component\Workflow\WorkflowInterface;

class ReturnBaseType extends AbstractType
{
    const string SAVE = 'save';
    const string MARK_AS_COMPLETED = 'mark_as_completed';
    const string MARK_AS_IN_PROGRESS = 'mark_as_in_progress';

    public function __construct(protected WorkflowInterface $completionStateStateMachine){}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var CompletionStatus $completionStatus */
        $completionStatus = $options['completion_status'];

        $transitions = array_filter(
            $this->completionStateStateMachine->getEnabledTransitions($completionStatus),
            fn (Transition $t) => $this->completionStateStateMachine->getMetadataStore()->getTransitionMetadata($t)['show_on_form'] ?? false
        );
        if (count($transitions) === 1) {
            $secondaryButtonName = array_pop($transitions)->getName();
        } else {
            throw new \RuntimeException('expecting single enabled transition');
        }

        $builder->add('buttons', ButtonGroupType::class, [
            'priority' => -1000,
        ]);

        $builder->get('buttons')
            ->add(self::SAVE, ButtonType::class, [
                'type' => 'submit',
                'label' => 'forms.buttons.save',
            ])
            ->add($secondaryButtonName, ButtonType::class, [
                'type' => 'submit',
                'label' => "forms.buttons.transition_{$secondaryButtonName}",
                'attr' => ['class' => 'govuk-button--secondary'],
            ])
            ->add('cancel', LinkType::class, [
                'label' => 'forms.buttons.cancel',
                'href' => $options['cancel_url'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired('cancel_url')
            ->setAllowedTypes('cancel_url', 'string')
            ->setRequired('completion_status')
            ->setAllowedTypes('completion_status', SectionStatusInterface::class);
    }
}
