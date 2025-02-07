<?php

namespace App\ListPage;

use App\Entity\Enum\Fund;
use App\Entity\FundReturn\FundReturn;
use App\Repository\SchemeFund\SchemeFundRepository;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\QueryBuilder;
use Ghost\GovUkCoreBundle\ListPage\AbstractListPage;
use Ghost\GovUkCoreBundle\ListPage\Field\ChoiceFilter;
use Ghost\GovUkCoreBundle\ListPage\Field\Simple;
use Ghost\GovUkCoreBundle\ListPage\Field\TextFilter;
use Ghost\GovUkCoreBundle\ListPage\ListPageData;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\RouterInterface;

class SchemeListPage extends AbstractListPage
{
    protected FundReturn $fundReturn;

    public function __construct(protected SchemeFundRepository $schemeFundRepository, FormFactoryInterface $formFactory, RouterInterface $router)
    {
        parent::__construct($formFactory, $router);
    }

    public function setFundReturn(FundReturn $fundReturn): static {
        $this->fundReturn = $fundReturn;
        return $this;
    }

    public function getPageUrl(int $page, bool $excludeRequestData = false, bool $excludeOrderData = false, array $extraData = []): string
    {
        return parent::getPageUrl($page, $excludeRequestData, $excludeOrderData, $extraData).'#scheme-list';
    }

    #[\Override]
    protected function getFieldsDefinition(): array
    {
        return [
            (new TextFilter('Name', 'scheme.name'))->sortable(),
            (new Simple('Ready for signoff?', '')),
            (new Simple('Retained?', 'schemeFund.retained')),
            (new Simple('On-track rating', '')),
        ];
    }

    #[\Override]
    protected function getQueryBuilder(): QueryBuilder
    {
        // We get the schemeFunds from this direction, so that we can list all of them and explicitly any that
        // do not requiring a return, if that is the case (e.g. CRSTS - if not retained and not quarter 1)

        // (Fetching via fundReturn->getSchemeReturns() direction would only fetch those schemes that
        //  do have returns, resulting in an incomplete list)
        return $this->schemeFundRepository->getQueryBuilderForSchemeFundsForAuthority(
            $this->fundReturn->getFundAward()->getAuthority(),
            $this->fundReturn->getFund(),
        );
    }

    #[\Override]
    public function getData(): ListPageData
    {
        $data = parent::getData();

        $schemeFunds = $data->getEntities();

        $entityGenerator = function() use ($schemeFunds) : \Generator {
            foreach($schemeFunds as $schemeFund) {
                $schemeReturn = $this->fundReturn->getSchemeReturnForSchemeFund($schemeFund);
                yield new SchemeListPageDataEntry($schemeFund, $schemeReturn);
            }
        };

        return new ListPageData(
            $data->getPage(),
            $data->getNumPages(),
            $data->getNumRecords(),
            $entityGenerator(),
            $data->getNextUrl(),
            $data->getPreviousUrl(),
            $data->getPaginationUrls(),
            $data->getFields(),
            $data->getOrderUrlGenerator(),
            $data->getOrder(),
            $data->getOrderDirection(),
        );
    }


    #[\Override]
    protected function getDefaultOrder(): array
    {
        return [
            Simple::generateId('Name') => 'ASC',
        ];
    }
}
