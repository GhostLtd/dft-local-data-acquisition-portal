<?php

namespace App\Form\Type\Admin;

use App\Entity\Authority;
use App\Entity\Enum\Fund;
use App\Entity\Enum\Role;
use App\Form\Type\BaseButtonsFormType;
use Ghost\GovUkFrontendBundle\Form\Type\ChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Translation\TranslatableMessage;

class FundAwardType extends AbstractType
{
    public function __construct(
        protected AuthorizationCheckerInterface $authorizationChecker,
        protected FundAwardDataMapper           $fundAwardDataMapper,
    ) {}

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $authority = $options['data'];

        if (!$authority instanceof Authority) {
            throw new UnexpectedTypeException($authority, Authority::class);
        }

        $canRemoveStatus = [];
        foreach($authority->getFundAwards() as $fundAward) {
            $canRemoveStatus[$fundAward->getType()->value] = $this->authorizationChecker->isGranted(
                Role::CAN_REMOVE_FUND_AWARD,
                $fundAward
            );
        }

        $funds = array_map(fn(Fund $f) => $f->value, Fund::enabledCases());

        $builder
            ->setDataMapper($this->fundAwardDataMapper)
            ->add('funds', ChoiceType::class, [
                'choices' => array_combine($funds, $funds),
                'choice_label' => fn(string $choice) => new TranslatableMessage("enum.fund.{$choice}", [], 'messages'),
                'choice_attr' => fn(string $choice) => ($canRemoveStatus[$choice] ?? true) ? [] : ['disabled' => true],
                'label' => false,
                'help' => 'pages.authority_edit_fund_awards.help',
                'multiple' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Authority::class,
            'translation_domain' => 'admin',
        ]);
    }

    public function getParent(): string
    {
        return BaseButtonsFormType::class;
    }
}