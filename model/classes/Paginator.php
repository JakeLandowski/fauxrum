<?php
/**
 *  Class used to represent a thread and create threads.
 *   
 *  DataCore => Validator => Registration
 */

/**
 *  Class used to represent a thread and create threads.
 *  
 *  @author Jacob Landowski
 */
class Paginator extends DataCore 
{
    protected $data = 
    [
        'start' => null,
        'per'   => null,
        'page'  => null,
        'order' => null,
        'total' => null,
        'num_pages'  => null,
    ];

    public function __construct($page, $per, $order)
    {
        $this->setValue('page',  $page);
        $this->setValue('per',   $per);
        $this->setValue('order', $order);
        $this->setValue('start', ($page - 1) * $per);
    }

    public function getAndPaginateAll($class, $threadId=null)
    {
        if(!is_callable([$class, 'getAllFromDatabase']))
            CustomError::throw("$class given does not have method getAllFromDatabase(), 
                                which is needed for Paginator to work.", 2);

        $result;

        if($this->getValue('page') == 0)
        {
            if($class === 'Thread')
                $this->_modifyStart(Thread::getNumThreads());
            else if($class === 'Post')
                $this->_modifyStart(Post::getNumPosts($threadId));
        }
        
        $result = $class::getAllFromDatabase($this->getValue('start'), 
                                                $this->getValue('per'),
                                                $this->getValue('order'),
                                                $threadId);
        if(isset($result['total'])) 
        {
            $total = $result['total'];
            $per   = $this->getValue('per');
            $this->setValue('total', $total);

            $numPages = (int)($total / $per) + ($total % $per != 0 ? 1 : 0);
            $this->setValue('num_pages', $numPages);
        }

        return $result;
    }

    public function isValidPage()
    {
        $page     = $this->getValue('page');
        $numPages = $this->getValue('num_pages');
        return isset($page) && isset($numPages) && $page <= $numPages;
    }

    public function getHiveTokens()
    {
        return 
        [
            'start'     => $this->getValue('start'),
            'page'      => $this->getValue('page'),
            'per'       => $this->getValue('per'),
            'order'     => $this->getValue('order'),
            'num_pages' => $this->getValue('num_pages'),
            'total'     => $this->getValue('total')
        ];
    }

  //=========================================================//
 //                   PRIVATE FUNCTIONS                     //
//=========================================================//

    private function _modifyStart($total)
    {
        $per = $this->getValue('per');
        $numPages = (int)($total / $per) + ($total % $per != 0 ? 1 : 0);
        $this->setValue('page', $numPages);
        $this->setValue('start', ($numPages - 1) * $per);
    }   

}