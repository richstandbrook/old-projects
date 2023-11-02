<?php
/**
* qbuild is designed to make it much easier to dynamicly build queries.
* and output database data staight to associated arrays or xml
*
* @package qbuild
*/

/**
* @include The core class to build the sql string
*/
require_once 'qbuilder.php';

/**
* This is the wraparound class that gives access to
* all the qbuilder features aswell as array and xml io
*
* @copyright © 2004, koorb.co.uk
* @author Richard Standbrook <richard@koorb.co.uk>
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
* @version 0.1
*/
class qbuild extends qbuilder {


    /**
    * Constructor
    *
    * @param string  Host address.
    * @param string  DB username
    * @param string  DB password
    * @param string  Database name
    * @param boolean Optional - Gives a persistant connection if set true.
    *
    * @return boolean true on success, false if there was an error
    * @access constructor
    */
    function qbuild($host, $user, $pass, $db, $persist = false) {

        $this->query['db_name'] = $db;

        require_once 'db.mysql.php';
        db_connect($host, $user, $pass, $db, $persist);

        return true;
    }


    /**
    * Loads rows from the table to an associated array using sql code
    *
    * @param string SQL code
    * @access public
    */
    function to_array_sql($sql) {

        // save to property for use in pager()
        $this->sql = $sql;

        $result = db_query($sql);
        $c = 0;

        while($row = db_array($result))
        {
            foreach($row as $key => $value)
            {
                $row[$key] = $value;
            }

            $results[$c] = $row;
            $c++;
        }

        if( !db_numrows($result) )
        {
            return false;

        }else{

            return $results;
        }
    }


    /**
    * Loads rows from the table to an associated array
    *
    * @param string table name
    * @param string query action
    *
    * @access public
    */
    function to_array($table, $action = 'SELECT') {

        $sql = $this->render($table, $action);
        $results = $this->to_array_sql($sql);

        return $results;
    }


    /**
    * Create xml from sql string
    *
    * @param string optionaly specify custom sql
    *
    * @access public
    */
    function to_xml_sql($sql) {

        // save to property for use in pager()
        $this->sql = $sql;

        $array = $this->to_array_sql($sql);

        if(!$array)
        {
            return false;
        }

        require_once('xml.php');

        foreach( $array as $offset => $child )
        {
            $xml_array['row'.$offset] = $child;
        }

        $xmlDoc = new MiniXMLDoc();
        @$xmlDoc->fromArray($xml_array);

        return $xml_in = $xmlDoc->toString();
    }


    /**
    * Create xml from an associated array
    *
    * @param string table name
    * @param string query action
    *
    * @access public
    */
    function to_xml($table, $action = 'SELECT') {

        $sql = $this->render($table, $action);
        $results = $this->to_xml_sql($sql);

        return $results;
    }


    /**
    * Returns record set navigation links
    *
    * @return array
    * @access public
    */
    function pager($range = 5,$append = null) {

        // make it easier
        $query   = $this->query;
        $get_var = $this->pager_var;

        // get results without limits
        $sql  = $this->render($query['table'],$query['action'],'NO_LIMIT');
        $rows = $this->to_array_sql($sql);
        $total_rows = count($rows);

        $total_pages = ceil($total_rows / $query['limit']);

        $current_page = $query['offset'] / $query['limit']+1;
        $current_page = $current_page > $total_pages ? $total_pages+1 : $current_page;
        $current_page = $current_page < 1 ? 0 : $current_page;

        $page_links = '';

        // loop through pages
        for ($page=1; $page<$total_pages+1; $page++)
        {
            // if the page is in the range add it to the output buffer
            if ($page >= ($current_page-$range) && $page <= ($current_page+$range) )
            {
                // if its the current page dont make it a link!
                if ($page == $current_page)
                {
                    $page_links .= ' <span class="qb_current_page">['.$page.']</span> ';

                }else{

                    $page_links .= '<a href="?'.$get_var.'='.$page.$append.'">'.$page.'</a> ';
                }
            }
        }

        $next_page = $current_page+1;
        $last_page = $current_page-1;

        require_once 'pager_links.php';

        $next_link = $current_page < $total_pages ? $next_link : '';
        $back_link = $current_page > 1 ? $back_link : '';

        $nav_append   = $current_page+$range < $total_pages ? '...'.$last_link : '';
        $nav_prepend  = $current_page-$range > 1 ? $first_link.'...' : '';

        $nav = $nav_prepend.$back_link.$page_links.$next_link.$nav_append;

        $nav = 'pages ('.$total_pages.'): '.$nav;

        return $nav;
    }


    /**
    * Saves a record to the table from an associated array
    *
    * Data Array should be ['field'] = 'value';
    *
    * @param string table name
    * @param array  data
    * @param string query action
    *
    * @return boolean result of query
    * @access public
    */
    function from_array($table, $data, $action = 'INSERT') {

        foreach ($data as $field => $value)
        {
            $this->field($field, $value);
        }

        $sql = $this->render($table, $action);

        return db_query($sql);
    }
}

?>
