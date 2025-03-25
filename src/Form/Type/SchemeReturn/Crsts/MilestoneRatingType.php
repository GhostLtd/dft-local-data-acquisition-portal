<?php

namespace App\Form\Type\SchemeReturn\Crsts;

use App\Entity\Enum\OnTrackRating;
use App\Entity\SchemeReturn\CrstsSchemeReturn;
use App\Form\Type\BaseButtonsFormType;
use Ghost\GovUkFrontendBundle\Form\Type\ChoiceType;
use Ghost\GovUkFrontendBundle\Form\Type\TextareaType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MilestoneRatingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('onTrackRating', ChoiceType::class, [
                'label' => "forms.scheme.milestone_progress.on_track_rating.label",
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                /** @link /templates/frontend/theme.html.twig */
                'help' => 'value overridden in theme',
                'choices' => OnTrackRating::cases(),
                'choice_label' => fn(OnTrackRating $choice) => "enum.on_track_rating.{$choice->value}",
                'choice_value' => fn(?OnTrackRating $choice) => $choice?->value,
                'expanded' => false,
                'placeholder' => 'forms.generic.placeholder',
            ])
            ->add('progressUpdate', TextareaType::class, [
                'label' => "forms.scheme.milestone_progress.progress_update.label",
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'help' => "forms.scheme.milestone_progress.progress_update.help",
            ])
            ->add('risks', TextareaType::class, [
                'label' => 'forms.scheme.milestone_progress.risks.label',
                'label_attr' => ['class' => 'govuk-label--s'],
                'help' => 'forms.scheme.milestone_progress.risks.help',
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
            'validation_groups' => 'milestone_rating',
        ]);
    }
}
