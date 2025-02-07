<?php

namespace App\ListPage;

use App\Entity\FundReturn\FundReturn;
use App\Repository\SchemeFund\SchemeFundRepository;
use Doctrine\ORM\QueryBuilder;
use Ghost\GovUkCoreBundle\ListPage\AbstractListPage;
use Ghost\GovUkCoreBundle\ListPage\Field\ChoiceFilter;
use Ghost\GovUkCoreBundle\ListPage\Field\Simple;
use Ghost\GovUkCoreBundle\ListPage\Field\TextFilter;
use Ghost\GovUkCoreBundle\ListPage\ListPageData;
use Ghost\GovUkCoreBundle\ListPage\ListPageForm;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
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
            (new ChoiceFilter('Ready for signoff?', 'schemeReturn.readyForSignoff', ['No' => 0, 'Yes' => 1])),
            (new ChoiceFilter('Retained?', 'schemeFund.retained', ['No' => 0, 'Yes' => 1])),
            // Don't see a way to filter this without using sub-queries and rewriting ListPage.
            // There's a possibility a view might help
            (new Simple('On-track rating', '')),
        ];
    }

    #[\Override]
    public function getFiltersForm(): FormInterface
    {
        static $form;

        $url = $this->getPageUrl($this->page, true, true);

        if (!$form) {
            $form = $this->formFactory->create(ListPageForm::class, null, [
                'fields' => $this->getFields(),
                'action' => $url,
            ]);
        }

        return $form;
    }

    #[\Override]
    protected function getQueryBuilder(): QueryBuilder
    {
        return $this->schemeFundRepository->getQueryBuilderForSchemeReturnsForFundReturn($this->fundReturn);
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
