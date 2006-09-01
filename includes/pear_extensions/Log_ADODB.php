<?php

/************************************************************
 *
 *	$Date$
 *	$Revision$
 *	$Author$
 *	$HeadURL$
 *
 /***********************************************************/

/* This file is adapted from the Log_sql class included with the
	 standard PEAR::Log package.
*/


class Log_ADODB extends Log
{
    /**
     * Variable containing the DSN information.
     * @var mixed
     * @access private
     */
    var $_dsn = '';

    /**
     * String containing the SQL insertion statement.
     *
     * @var string
     * @access private
     */
    var $_sql = '';

    /**
     * Array containing our set of DB configuration options.
     * @var array
     * @access private
     */
    var $_options = array('persistent' => true);

    /**
     * Object holding the database handle.
     * @var object
     * @access private
     */
    var $_db = null;

    /**
     * Resource holding the prepared statement handle.
     * @var resource
     * @access private
     */
    var $_statement = null;

    /**
     * Flag indicating that we're using an existing database connection.
     * @var boolean
     * @access private
     */
    var $_existingConnection = false;

    /**
     * String holding the database table to use.
     * @var string
     * @access private
     */
    var $_table = 'log_table';

    /**
     * String holding the name of the ID sequence.
     * @var string
     * @access private
     */
    var $_sequence = 'log_id';

    /**
     * Maximum length of the $ident string.  This corresponds to the size of
     * the 'ident' column in the SQL table.
     * @var integer
     * @access private
     */
    var $_identLimit = 16;


    /**
     * Constructs a new sql logging object.
     *
     * @param string $name         The target SQL table.
     * @param string $ident        The identification field.
     * @param array $conf          The connection configuration array.
     * @param int $level           Log messages up to and including this level.
     * @access public
     */
    function Log_ADODB($name, $ident = '', $conf = array(),
                     $level = PEAR_LOG_DEBUG)
    {
        $this->_id = md5(microtime());
        $this->_table = $name;
        $this->_mask = Log::UPTO($level);

        /* Now that we have a table name, assign our SQL statement. */
        if (!empty($this->_sql)) {
            $this->_sql = $conf['sql'];
        } else {
            $this->_sql = 'INSERT INTO ' . $this->_table .
                          ' (id, logtime, ident, priority, message)' .
                          ' VALUES(?, ?, ?, ?, ?)';
        }

        /* If an options array was provided, use it. */
        if (isset($conf['options']) && is_array($conf['options'])) {
            $this->_options = $conf['options'];
        }

        /* If a specific sequence name was provided, use it. */
        if (!empty($conf['sequence'])) {
            $this->_sequence = $conf['sequence'];
        }

        /* If a specific ident Limit was provided, use it. */
        if (isset($conf['identLimit'])) {
            $this->_identLimit = $conf['identLimit'];
        }

        /* Now that the ident limit is confirmed, set the ident string. */
        $this->setIdent($ident);

        /* If an existing database connection was provided, use it. */
        if (isset($conf['db'])) {
            $this->_db = &$conf['db'];
            $this->_existingConnection = true;
            $this->_opened = true;
        } else {
            $this->_dsn = $conf['dsn'];
        }
    }


    /**
     * Sets this Log instance's identification string.  Note that this
     * SQL-specific implementation will limit the length of the $ident string
     * to sixteen (16) characters.
     *
     * @param string    $ident      The new identification string.
     *
     * @access  public
     * @since   Log 1.8.5
     */
    function setIdent($ident)
    {
        $this->_ident = substr($ident, 0, $this->_identLimit);
    }

    /**
     * Inserts $message to the currently open database.  Calls open(),
     * if necessary.  Also passes the message along to any Log_observer
     * instances that are observing this Log.
     *
     * @param mixed  $message  String or object containing the message to log.
     * @param string $priority The priority of the message.  Valid
     *                  values are: PEAR_LOG_EMERG, PEAR_LOG_ALERT,
     *                  PEAR_LOG_CRIT, PEAR_LOG_ERR, PEAR_LOG_WARNING,
     *                  PEAR_LOG_NOTICE, PEAR_LOG_INFO, and PEAR_LOG_DEBUG.
     * @return boolean  True on success or false on failure.
     * @access public
     */
    function log($message, $priority = null)
    {
        /* If a priority hasn't been specified, use the default value. */
        if ($priority === null) {
            $priority = $this->_priority;
        }

        /* Abort early if the priority is above the maximum logging level. */
        if (!$this->_isMasked($priority)) {
            return false;
        }

        /* If the connection isn't open and can't be opened, return failure. */
        if (!$this->_opened && !$this->open()) {
            return false;
        }

        /* Extract the string representation of the message. */
        $message = $this->_extractMessage($message);

        /* Build our set of values for this log entry. */
        $id = $this->_db->GenID($seqName = $this->_sequence);
        $values = array($id, $this->_db->BindTimeStamp(time()), $this->_ident, $priority, $message);

        /* Execute the SQL query for this log entry insertion. */
        $result =& $this->_db->Execute($this->_sql, $values);
        if (FALSE === $result) {
            return false;
        }

        $this->_announce(array('priority' => $priority, 'message' => $message));

        return true;
    }

}
