<?php

namespace App\Utility\SignoffHelper;

use App\Entity\FundReturn\FundReturn;
use App\Entity\SchemeReturn\SchemeReturn;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class SignoffHelperFactory
{
    public function __construct(
        /** @var array<SignoffHelperInterface> */
        #[AutowireIterator(SignoffHelperInterface::class)]
        protected iterable $signoffHelpers,
    ) {}

    /**
     * @param FundReturn|SchemeReturn $return
     * @return SignoffHelperInterface
     */
    public function getHelperFor(FundReturn|SchemeReturn $return): SignoffHelperInterface
    {
        foreach($this->signoffHelpers as $signoffHelper) {
            if ($signoffHelper->supports($return)) {
                return $signoffHelper;
            }
        }

        throw new \RuntimeException("No signoff helper supporting ".$return::class." found");
    }
}
