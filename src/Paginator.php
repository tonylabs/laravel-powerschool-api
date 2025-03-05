<?php

namespace TONYLABS\PowerSchool\Api;

class Paginator
{
    protected int $page = 1;
    protected RequestBuilder $objRequestBuilder;
    protected int $pageSize;

    public function __construct(RequestBuilder $builder, int $pageSize = 100)
    {
        $this->pageSize = $pageSize;
        $this->objRequestBuilder = $builder->pageSize($pageSize);
    }

    /**
     * Get the next page of results
     * 
     * @return Response|null
     */
    public function page(): ?Response
    {
        $arrayResults = $this->objRequestBuilder->page($this->page)->send(false);

        // Handle empty results
        if ($arrayResults->isEmpty()) {
            $this->reset();
            return null;
        }

        // Handle single record wrapped in an array
        if (!$arrayResults[0]) {
            $arrayResults->setData([$arrayResults->data]);
        }
        
        $this->page += 1;
        return $arrayResults;
    }
    
    /**
     * Reset the paginator to the first page
     * 
     * @return self
     */
    public function reset(): self
    {
        $this->page = 1;
        return $this;
    }
    
    /**
     * Get the current page number
     * 
     * @return int
     */
    public function getCurrentPage(): int
    {
        return $this->page;
    }
    
    /**
     * Get the page size
     * 
     * @return int
     */
    public function getPageSize(): int
    {
        return $this->pageSize;
    }
}
