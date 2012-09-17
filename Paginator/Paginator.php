<?php
namespace Avro\PaginatorBundle\Paginator;

use Doctrine\Common\Persistence\ObjectManager;

/**
 * Paginator
 *
 * @author Joris de Wit <joris.w.dewit@gmail.com>
 */
class Paginator implements PaginatorInterface
{
    protected $om;
    protected $qb;
    protected $uri;
    protected $sort;
    protected $page;
    protected $class;
    protected $routeParameters;
    protected $limit;
    protected $filter;
    protected $filters = array();
    protected $options;
    protected $minPage = 1;
    protected $request;
    protected $maxLimit;
    protected $paginator;
    protected $direction;
    protected $limitSteps;
    protected $resultCount;
    protected $initialSort;
    protected $buttonCount;
    protected $initialDirection;
    protected $constraints = array();


    public function __construct(ObjectManager $om, $request, array $options)
    {
        $this->om = $om;
        $this->options = $options;
        $this->request = $request;
    }

    /*
     * Get the last paginator button
     *
     * @return int $maxPage
     */
    protected function getMaxPage()
    {
        $buttons = ceil($this->resultCount / $this->limit);

        if ($buttons > $this->buttonCount) {
            $diff = ($this->buttonCount - 1) / 2;

            $minPage = $this->currentPage - $diff;
            if ($minPage < 1) {
                $under = 1 - $minPage;
                $minPage = 1;
                $diff = $diff + $under;
            }
            $this->minPage = $minPage;

            $maxPage = $this->currentPage + $diff;

            if ($maxPage > $buttons) {
                $over = $maxPage - $buttons;
                $this->minPage = $minPage - $over;
                $maxPage = $buttons;
            }

            return $maxPage;
        } else if ($buttons < 1) {
            return 1;
        } else {
            return $buttons;
        }
    }
    public function getLastPage()
    {
        $lastPage = ceil($this->resultCount / $this->limit);

        if ($lastPage < 1) {
            return 1;
        }

        return $lastPage;
    }

    /*
     * Set defaultOptions
     *
     * @param string $paginatorName
     */
    public function setOptions($paginatorName)
    {
        $options = $this->options[$paginatorName];

        $this->dbDriver = $options['db_driver'];
        $this->buttonCount = $options['button_count'];
        $this->defaultLimit = $options['default_limit'];
        $this->limit = $this->request->query->get('limit') ?: $options['default_limit'];
        $this->maxLimit = $options['max_limit'];
        $this->limitSteps = $options['limit_steps'];
        $this->direction = $this->request->query->get('direction') ?: ($this->initialDirection ?: $options['default_direction']);
        $this->sort = $this->request->query->get('sort') ?: $this->initialSort;
        $this->filter = $this->request->query->get('filter') ?: '';
        $this->filterValue = $this->request->query->get('filterValue') ?: '';
        $this->currentPage = $this->request->query->get('page') ?: 1;
        $this->route = $this->request->get('_route');
    }

    public function getOptions()
    {
        $options = array(
            'paginatorTemplate' => 'AvroPaginatorBundle:Paginator:paginator.html.twig',
            'pagerTemplate' => 'AvroPaginatorBundle:Paginator:pager.html.twig',
            'headingTemplate' => 'AvroPaginatorBundle:Paginator:heading.html.twig',
            'limitTemplate' => 'AvroPaginatorBundle:Paginator:limit.html.twig',
            'filterTemplate' => 'AvroPaginatorBundle:Paginator:filter.html.twig',
            'resultCount' => $this->resultCount,
            'direction' => $this->direction,
            'defaultLimit' => $this->defaultLimit,
            'maxLimit' => $this->maxLimit,
            'limit' => $this->limit,
            'limitSteps' => $this->limitSteps,
            'sort' => $this->sort,
            'sortAscClass' => 'icon-chevron-down',
            'sortDescClass' => 'icon-chevron-up',
            'filter' => $this->filter,
            'filterValue' => $this->filterValue,
            'filters' => $this->filters,
            'containerClass' => 'pagination pagination-centered',
            'route' => $this->route,
            'routeParams' => array('page' => $this->currentPage, 'limit' => $this->limit, 'sort' => $this->sort, 'direction' => $this->direction, 'filter' => $this->filter),
            'disabledClass' => 'disabled',

            'firstPageText' => 'first',
            'firstPage' => 1,

            'prevPageText' => '«',
            'prevPage' => ($this->currentPage - 1) <= 1 ? ($this->currentPage - 1) : 1,

            'maxPage' => $this->getMaxPage(),
            'minPage' => $this->minPage,

            'currentPage' => $this->currentPage,
            'currentClass' => 'active',

            'nextPage' => $this->currentPage + 1,
            'nextPageText' => '»',

            'lastPage' => $this->getLastPage(),
            'lastPageText' => 'last',
        );

        if ($this->routeParameters) {
            $options['routeParams'] = array_merge($this->routeParameters, $options['routeParams']);
        }

        return $options;
    }

    /*
     * Set the class
     *
     * @param Document $class The class namespace
     */
    public function setClass($class)
    {
        $this->class = $class;
    }

    /*
     * Get result count
     *
     * @return int $resultCount
     */
    public function getResultCount()
    {
        return $this->resultCount;
    }

    public function sortBy($field, $direction = '')
    {
        $this->initialSort = $field;
        $this->initialDirection = $direction;
    }

    /*
     * Execute the queryBuilder
     *
     * @param string paginatorName
     * @return MongoCursor
     */
    public function getResults($paginatorName = 'default')
    {
        $this->setOptions($paginatorName);

        if ($this->dbDriver == 'mongodb') {

            $qb = $this->om->createQueryBuilder($this->class)
                ->limit($this->limit)
                ->skip(($this->currentPage - 1) * $this->limit)
            ;

            if ($this->sort) {
                $qb->sort($this->sort, $this->direction);
            }

            if ($this->filter) {
                $field = $this->filters[$this->filter]['field'];
                $value = $this->filterValue;
                if ($value) {
                    if ($value == "1") {
                        $qb->field($field)->equals(true);
                    } else {
                        $qb->field($field)->equals($value);
                    }
                } else {
                    $qb->field($field)->notEqual(true);
                }
            }

            foreach ($this->constraints as $constraint) {
                switch ($constraint['condition']) {
                    case 'equals':
                        $qb->field($constraint['field'])->equals($constraint['value']);
                    break;
                    case 'notEqual':
                        $qb->field($constraint['field'])->notEqual($constraint['value']);
                    break;
                }
            }

            $results = $qb->getQuery()->execute();

            $this->resultCount = count($results);

        }


        return $results;
    }

    public function addConstraint($field, $value, $condition = 'equals')
    {
        $this->constraints[] = array('field' => $field, 'value' => $value, 'condition' => $condition);
    }

    public function addFilters(array $filters)
    {
        $this->filters = $filters;
    }

    public function addRouteParameters($routeParameters)
    {
        $this->routeParameters = $routeParameters;
    }
}
