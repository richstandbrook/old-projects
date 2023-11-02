<?php
/**
* qbuild is designed to make it much easier to dynamicly build queries.
* and output database data staight to associated arrays or xml
*
* @package qbuild
*/

/**
* This is the core class that build the sql string
*
* @copyright © 2004, koorb.co.uk
* @author Richard Standbrook <richard@koorb.co.uk>
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
* @version 0.3
*/
class qbuilder {

    /**
    * @var int Sets the number of the first page. 0 or 1
    * Note: setting to 0 will cause problems with the qbuild::pager() method
    * @see page
    * @access private
    */
    var $first_page = 1;

    /**
    * @var string Get var for page number
    * @see qbuild::pager()
    * @access private
    */
    var $pager_var = 'page_number';

    /**
    * @var string relative server path to include files
    * @access private
    */
    var $includes = 'qbuild/';

    /**
    * @var array Stores query segments
    * @access private
    */
    var $query = array();

    /**
    * @var string Any error output to this string
    * @acess private
    */
    var $error_buffer;

    /**
    * @var array Valid query actions
    * @var array Valid order types
    * @var array Valid comparison operations
    * @var array Valid logical operations
    *
    * @access private
    */
    var $actions = array ('SELECT','DELETE','INSERT','UPDATE');
    var $orders  = array ('ASC','DESC');
    var $comops  = array ('=','<','>','!=','<=','>=','<=>','LIKE');
    var $logic   = array ('AND','&&','OR','||','XOR','NOT','!');


    /**
    * Simple error method, set error_buffer as msg and returns false
    *
    * @return boolean false
    * @access private
    */
    function error($msg) {

        $this->error_buffer = $msg;
        return false;
    }

    /**
    * Add a condition to the query
    *
    * @param string The name of the field
    * @param string The intended value of the field
    * @param string Comparison operation = > != etc.
    * @param string Logic, AND, OR, NOT etc. Used only if this condition is preceded by another
    * For example ... field = value AND field1 = value1 ...
    * For the second condition in the example, the logic parameter `AND` is given
    *
    * @return boolean true or false on error
    * @access public
    *
    * There are two ways to use this method
    *
    * 1. Pass an associated array of parameters
    *
    * <code>
    * $condition = Array('field' => $field_name,
    *                    'comop' => $comprison,
    *                    'value' => $field_value,
    *                    'logic' => $logic);
    *
    * $qb->condition($condition);
    * </code>
    *
    * 2. As a full condition string
    *
    * <code>
    * $qb->condition('OR field != value');
    * </code>
    */
    function condition( $condition ) {

        // if $condition is an string match segments for array
        if (!is_array($condition))
        {
            $pattern = "/^[ ]*((?:[^ ]+)[ ]+)?([^ ]+)[ ]+([^ ]+)[ ]+([^ ]+)$/";
            preg_match($pattern,$condition,$con_matches);

            $condition = array();
            $condition['logic'] = trim($con_matches[1]);
            $condition['field'] = trim($con_matches[2]);
            $condition['comop'] = trim($con_matches[3]);
            $condition['value'] = trim($con_matches[4]);
        }

        // make sure logic is uppercase
        @$condition['logic'] = strtoupper($condition['logic']);

        // error if the logic is not valid
        if ($condition['logic'] && !in_array($condition['logic'],$this->logic))
        { return $this->error('Logic '.$condition['logic'].' is not valid'); }

        // error if the comparison operation is not valid
        if ($condition['comop'] && !in_array($condition['comop'],$this->comops))
        { return $this->error('Comparison '.$condition['comop'].' is not valid'); }

        // add condition to query array
        $this->query['conditions'][] = $condition;

        return true;
    }

    /**
    * Add a field and optional value to the query
    *
    * @param string Name of the field
    * @param string Value intended value to set to field
    *
    * @return void
    * @access public
    */
    function field($name, $value = null) {

        $this->query['fileds'][$name] = $value;
    }

    /**
    * Set the order of results
    * note: to set the order without a sort field set $field as null
    *
    * @param string name of the field to sort by
    * @param string ASC or DESC order of results
    *
    * @return boolean true or false on error
    * @access public
    */
    function order($field, $order = 'ASC') {

        // make order uppercase
        $order = strtoupper($order);

        // error if the order is not valid
        if ($order && !in_array($order,$this->orders))
        { return $this->error('Order '.$order.' is not valid'); }

        $this->query['order']['field'] = $field;
        $this->query['order']['order'] = $order;

        return true;
    }

    /**
    * Limit how many results are returned
    * If you suply both parameters the first is the limit offset
    * and the second the page limit. If you only suply one parameter
    * it will be seen as the page limit.
    *
    * @param int offset
    * @param int limit
    *
    * @return boolean true or false on error
    * @access public
    */
    function limit() {

        $args = func_get_args();
        $argC = count($args);

        // error if no args are given
        if (!$argC >= 1)
        { return $this->error('No limit data given'); }

        $this->query['offset'] = $argC > 1 ? $args[0] : 0;
        $this->query['limit']  = $argC > 1 ? $args[1] : $args[0];

        return true;
    }

    /**
    * Simply way to change the offset of the query.
    * This method can be used publicly but is also
    * called by the constructor if a GET value is set
    * that matches the $page_track class property
    *
    * @see page_track
    * @see qbuild
    *
    * @param int page number to return
    *
    * @return void
    * @access public
    */
    function page($page) {

        // error if no limit has been set
        if (!isset($this->query['limit']))
        { return $this->error('Cant get page, no limit set'); }

        $page = $this->first_page == 1 ? $page-1 : $page;

        $this->query['offset'] = $page * $this->query['limit'];
    }

    /**
    * Renders the query information
    * into the appropriate SQL string
    *
    * @see actions
    *
    * @param string name of table to query
    * @param string query action. default is SELECT
    *
    * @param string flags, currently only for internal
    * command to stop rendering with limit to get total
    * resalts for a query
    *
    * @return string SQL query
    * @access public
    */
    function render($table, $action = 'SELECT',$FLAGS = null) {

        // make action uppercase
        $action = strtoupper($action);

        // save to property for later use
        $this->query['table']  = $table;
        $this->query['action'] = $action;

        $query = $this->query;

        // error if action is not valid
        if (!in_array($action,$this->actions))
        { return $this->error('Invalid query action '.$action); }


            // comma seperated field and value lists
            $cs_fields = '';
            $cs_values = '';
            // comma seperated `field` = 'value' pairs
            $cs_fvpair = '';

            if (isset($query['fileds']))
            {
                foreach ($query['fileds'] as $field => $value)
                {
                    $cs_fields .= '`'.trim($field).'`, ';
                    $cs_values .= '\''.trim($value).'\', ';

                    $cs_fvpair .= $value === null ? null : '`'.trim($field).'` = \''.trim($value).'\',';
                }

            }else{

                $cs_fields = '*';
            }

            $cs_fields = trim($cs_fields,', ');
            $cs_values = trim($cs_values,', ');

            $cs_fvpair = trim($cs_fvpair,',');


        // build conditions string
        $condition = '';

        if (isset($query['conditions']))
        {
            $condition = ' WHERE ';

            foreach($query['conditions'] as $condition_meta)
            {
                $condition .= $condition_meta['logic'].' `'
                             .$condition_meta['field'].'` '
                             .$condition_meta['comop'].' \''
                             .$condition_meta['value'].'\' ';
            }

            $condition = rtrim($condition);
        }


            // build limit and order strings
            $limit = empty($query['limit']) || strstr($FLAGS,'NO_LIMIT') ? null : ' LIMIT '.$query['offset'].','.$query['limit'];
            $order = empty($query['order']['field']) ? null : ' ORDER BY `'.$query['order']['field'].'`';
            $order.= empty($query['order']['order']) ? null : ' '.$query['order']['order'];


        switch($action)
        {
            // build select query
            case 'SELECT':

                $sql = 'SELECT '.$cs_fields.' FROM `'.$table.'`'.$condition.$order.$limit;

            break;

            // build update query
            case 'UPDATE':

                $sql = 'UPDATE `'.$table.'` SET '.$cs_fvpair.$condition.$order.$limit;

            break;

            // build insert query
            case 'INSERT':

                $sql = 'INSERT INTO `'.$table.'` ( '.$cs_fields.' ) VALUES ( '.$cs_values.' )';

            break;

            // build delete query
            case 'DELETE':

                $sql = 'DELETE FROM `'.$table.'`'.$condition.$order.$limit;

            break;
        }

        return $sql;
    }
}

?>
