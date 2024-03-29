<?php


// Adapted from Translation2_Admin_Container_mdb2 class for use with mail2forum. 
// Original Copyright information follows:


/**
 * Contains the Translation2_Admin_Container_mdb2 class
 *
 * PHP versions 4 and 5
 *
 * LICENSE: Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 * 3. The name of the author may not be used to endorse or promote products
 *    derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR "AS IS" AND ANY EXPRESS OR IMPLIED
 * WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE FREEBSD PROJECT OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF
 * THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @category   Internationalization
 * @package    Translation2
 * @author     Lorenzo Alberton <l dot alberton at quipo dot it>
 * @copyright  2004-2005 Lorenzo Alberton
 * @license    http://www.debian.org/misc/bsd.license  BSD License (3 Clause)
 * @version    CVS: $Id: mdb2.php,v 1.27 2006/02/22 16:17:37 quipo Exp $
 * @link       http://pear.php.net/package/Translation2
 */

/**
 * require Translation2_Container_mdb2 class
 */
require_once 'Translation2/Container/adodb.php';

/**
 * Storage driver for storing/fetching data to/from a database
 *
 * This storage driver can use all databases which are supported
 * by the PEAR::MDB2 abstraction layer to store and fetch data.
 *
 * @category   Internationalization
 * @package    Translation2
 * @author     Lorenzo Alberton <l dot alberton at quipo dot it>
 * @copyright  2004-2005 Lorenzo Alberton
 * @license    http://www.debian.org/misc/bsd.license  BSD License (3 Clause)
 * @link       http://pear.php.net/package/Translation2
 */
class Translation2_Admin_Container_adodb extends Translation2_Container_adodb
{
    // {{{

    /**
     * Fetch the table names from the db
     * @access private
     * @return array|PEAR_Error
     */
    function _fetchTableNames()
    {
			return $this->db->MetaTables('TABLES');
    }

    // }}}
    // {{{ addLang()

    /**
     * Creates a new table to store the strings in this language.
     * If the table is shared with other langs, it is ALTERed to
     * hold strings in this lang too.
     *
     * @param array $langData
     * @return true|PEAR_Error
     */
    function addLang($langData)
    {
        $tables = $this->_fetchTableNames();
        if (PEAR::isError($tables)) {
            return $tables;
        }

        $lang_col = $this->_getLangCol($langData['lang_id']);

        if (in_array($langData['table_name'], $tables)) {
            //table exists
            $query = sprintf('ALTER TABLE %s ADD COLUMN %s TEXT',
                            $langData['table_name'],
                            $lang_col
            );
            ++$this->_queries;
            return $this->db->Execute($query);
        }

        //table does not exist
        $queries = array();
        $queries[] = sprintf('CREATE TABLE %s ( '
                             .'%s VARCHAR(50) default NULL, '
                             .'%s TEXT NOT NULL, '
                             .'%s TEXT )',
                             $langData['table_name'],
                             $this->options['string_page_id_col'],
                             $this->options['string_id_col'],
                             $lang_col
        );
        $mysqlClause = ($this->db->databaseType == 'mysql') ? '(255)' : '';
        $queries[] = sprintf('CREATE UNIQUE INDEX %s_%s_%s_index ON %s (%s, %s%s)',
                             $langData['table_name'],
                             $this->options['string_page_id_col'],
                             $this->options['string_id_col'],
                             $langData['table_name'],
                             $this->options['string_page_id_col'],
                             $this->options['string_id_col'],
                             $mysqlClause
        );
        $queries[] = sprintf('CREATE INDEX %s_%s_index ON %s (%s)',
                             $langData['table_name'],
                             $this->options['string_page_id_col'],
                             $langData['table_name'],
                             $this->options['string_page_id_col']
        );
        $queries[] = sprintf('CREATE INDEX %s_%s_index ON %s (%s%s)',
                             $langData['table_name'],
                             $this->options['string_id_col'],
                             $langData['table_name'],
                             $this->options['string_id_col'],
                             $mysqlClause
        );
        foreach($queries as $query) {
            ++$this->_queries;
            $res = $this->db->Execute($query);
            if (PEAR::isError($res)) {
                return $res;
            }
        }
        return true;
    }

    // }}}
    // {{{ addLangToList()

    /**
     * Creates a new entry in the langsAvail table.
     * If the table doesn't exist yet, it is created.
     *
     * @param array $langData array('lang_id'    => 'en',
     *                              'table_name' => 'i18n',
     *                              'name'       => 'english',
     *                              'meta'       => 'some meta info',
     *                              'error_text' => 'not available',
     *                              'encoding'   => 'iso-8859-1');
     * @return true|PEAR_Error
     */
    function addLangToList($langData)
    {
        $tables = $this->_fetchTableNames();
        if (PEAR::isError($tables)) {
            return $tables;
        }

        if (!in_array($this->options['langs_avail_table'], $tables)) {
            $queries = array();
            $queries[] = sprintf('CREATE TABLE %s ('
                                .'%s VARCHAR(16), '
                                .'%s VARCHAR(200), '
                                .'%s TEXT, '
                                .'%s VARCHAR(250), '
                                .'%s VARCHAR(16) )',
                                $this->options['langs_avail_table'],
                                $this->options['lang_id_col'],
                                $this->options['lang_name_col'],
                                $this->options['lang_meta_col'],
                                $this->options['lang_errmsg_col'],
                                $this->options['lang_encoding_col']
            );
            $queries[] = sprintf('CREATE UNIQUE INDEX %s_%s_index ON %s (%s)',
                                $this->options['langs_avail_table'],
                                $this->options['lang_id_col'],
                                $this->options['langs_avail_table'],
                                $this->options['lang_id_col']
            );

            foreach ($queries as $query) {
                ++$this->_queries;
                $res = $this->db->Execute($query);
                if (PEAR::isError($res)) {
                    return $res;
                }
            }
        }

        $query = sprintf('INSERT INTO %s (%s, %s, %s, %s, %s) VALUES (%s, %s, %s, %s, %s)',
	                $this->options['langs_avail_table'],
                    $this->options['lang_id_col'],
                    $this->options['lang_name_col'],
                    $this->options['lang_meta_col'],
                    $this->options['lang_errmsg_col'],
                    $this->options['lang_encoding_col'],
                    $this->db->quote($langData['lang_id']),
                    $this->db->quote($langData['name']),
                    $this->db->quote($langData['meta']),
                    $this->db->quote($langData['error_text']),
                    $this->db->quote($langData['encoding'])
        );

        ++$this->_queries;
        $success = $this->db->Execute($query);
        $this->options['strings_tables'][$langData['lang_id']] = $langData['table_name'];
        return $success;
    }

    // }}}
    // {{{ removeLang()

    /**
     * Remove the lang from the langsAvail table and drop the strings table.
     * If the strings table holds other langs and $force==false, then
     * only the lang column is dropped. If $force==true the whole
     * table is dropped without any check
     *
     * @param string  $langID
     * @param boolean $force
     * @return true|PEAR_Error
     */
    function removeLang($langID, $force)
    {
        //remove from langsAvail
        $query = sprintf('DELETE FROM %s WHERE %s = %s',
            $this->options['langs_avail_table'],
            $this->options['lang_id_col'],
            $this->db->quote($langID, 'text')
        );
        ++$this->_queries;
        $res = $this->db->Execute($query);
        if (PEAR::isError($res)) {
            return $res;
        }

        $lang_table = $this->_getLangTable($langID);
        if ($force) {
            //remove the whole table
            ++$this->_queries;
            return $this->db->Execute('DROP TABLE ' . $lang_table);
        }

        //drop only the column for this lang
        $query = sprintf('ALTER TABLE %s DROP COLUMN %s',
            $lang_table,
            $this->_getLangCol($langID)
        );
        ++$this->_queries;
        return $this->db->Execute($query);
    }

    // }}}
    // {{{ updateLang()

    /**
     * Update the lang info in the langsAvail table
     *
     * @param array  $langData
     * @return true|PEAR_Error
     */
    function updateLang($langData)
    {
        $allFields = array(
            //'lang_id'    => 'lang_id_col',
            'name'       => 'lang_name_col',
            'meta'       => 'lang_meta_col',
            'error_text' => 'lang_errmsg_col',
            'encoding'   => 'lang_encoding_col',
        );
        $updateFields = array_keys($langData);
        $langSet = array();
        foreach ($allFields as $field => $col) {
            if (in_array($field, $updateFields)) {
                $langSet[] = $this->options[$col] . ' = ' .
                             $this->db->quote($langData[$field]);
            }
        }
        $query = sprintf('UPDATE %s SET %s WHERE %s=%s',
            $this->options['langs_avail_table'],
            implode(', ', $langSet),
            $this->options['lang_id_col'],
            $this->db->quote($langData['lang_id'])
        );

        ++$this->_queries;
        $success = $this->db->Execute($query);
        $this->fetchLangs();  //update memory cache
        return ($success !== FALSE);
    }

    // }}}
    // {{{ add()

    /**
     * Add a new entry in the strings table.
     *
     * @param string $stringID
     * @param string $pageID
     * @param array  $stringArray Associative array with string translations.
     *               Sample format:  array('en' => 'sample', 'it' => 'esempio')
     * @return true|PEAR_Error
     */
    function add($stringID, $pageID, $stringArray)
    {
        $langs = array_intersect(
            array_keys($stringArray),
            $this->getLangs('ids')
        );

        if (!count($langs)) {
            //return error: no valid lang provided
            return true;
        }

        // Langs may be in different tables - we need to split up queries along
        // table lines, so we can keep DB traffic to a minimum.

        $unquoted_stringID = $stringID;
        $unquoted_pageID   = $pageID;
        $stringID = $this->db->quote($stringID, 'text');
        $pageID   = is_null($pageID) ? 'NULL' : $this->db->quote($pageID, 'text');
        // Loop over the tables we need to insert into.
        foreach ($this->_tableLangs($langs) as $table => $tableLangs) {
            $exists = $this->_recordExists($unquoted_stringID, $unquoted_pageID, $table);
            if (PEAR::isError($exists)) {
                return $exists;
            }
            $func  = $exists ? '_getUpdateQuery' : '_getInsertQuery';
            $query = $this->$func($table, $tableLangs, $stringID, $pageID, $stringArray);
            
            ++$this->_queries;
            $res = $this->db->Execute($query);
            if (PEAR::isError($res)) {
                return $res;
            }
        }

        return true;
    }

    // }}}
    // {{{ update()

    /**
     * Update an existing entry in the strings table.
     *
     * @param string $stringID
     * @param string $pageID
     * @param array  $stringArray Associative array with string translations.
     *               Sample format:  array('en' => 'sample', 'it' => 'esempio')
     * @return true|PEAR_Error
     */
    function update($stringID, $pageID, $stringArray)
    {
        return $this->add($stringID, $pageID, $stringArray);
    }

    // }}}
    // {{{ _getInsertQuery()

    /**
     * Build a SQL query to INSERT a record
     *
     * @access private
     * @return string
     */
    function _getInsertQuery($table, &$tableLangs, $stringID, $pageID, &$stringArray)
    {
        $tableCols = $this->_getLangCols($tableLangs);
        $langData = array();
        foreach ($tableLangs as $lang) {
            $langData[$lang] = $this->db->quote($stringArray[$lang], 'text');
        }

        return sprintf('INSERT INTO %s (%s, %s, %s) VALUES (%s, %s, %s)',
            $table,
            $this->options['string_id_col'],
            $this->options['string_page_id_col'],
            implode(', ', $tableCols),
            $stringID,
            $pageID,
            implode(', ', $langData)
        );
    }

    // }}}
    // {{{ _getUpdateQuery()

    /**
     * Build a SQL query to UPDATE a record
     *
     * @access private
     * @return string
     */
    function _getUpdateQuery($table, &$tableLangs, $stringID, $pageID, &$stringArray)
    {
        $tableCols = $this->_getLangCols($tableLangs);
        $langSet = array();
        foreach ($tableLangs as $lang) {
            $langSet[] = $tableCols[$lang] . ' = ' .
                         $this->db->quote($stringArray[$lang], 'text');
        }

        return sprintf('UPDATE %s SET %s WHERE %s = %s AND %s = %s',
            $table,
            implode(', ', $langSet),
            $this->options['string_id_col'],
            $stringID,
            $this->options['string_page_id_col'],
            $pageID
        );
    }

    // }}}
    // {{{ remove()

    /**
     * Remove an entry from the strings table.
     *
     * @param string $stringID
     * @param string $pageID
     * @return mixed true on success, PEAR_Error on failure
     */
    function remove($stringID, $pageID)
    {
        $tables = array_unique($this->_getLangTables());

        $stringID = $this->db->quote($stringID, 'text');
        // get the tables and skip the non existent ones
        $dbTables = $this->_fetchTableNames();
        foreach ($tables as $table) {
            if (!in_array($table, $dbTables)) {
                continue;
            }
            $query = sprintf('DELETE FROM %s WHERE %s = %s AND %s',
                             $table,
                             $this->options['string_id_col'],
                             $stringID,
                             $this->options['string_page_id_col']
            );
            if (is_null($pageID)) {
                $query .= ' IS NULL';
            } else {
                $query .= ' = ' . $this->db->quote($pageID, 'text');
            }

            ++$this->_queries;
            $res = $this->db->Execute($query);
            if (PEAR::isError($res)) {
                return $res;
            }
        }

        return true;
    }

    // }}}
    // {{{ getPageNames()
    
    /**
     * Get a list of all the pageIDs in any table.
     *
     * @return array
     */
    function getPageNames()
    {
        $pages = array();
        foreach ($this->_getLangTables() as $table) {
            $query = sprintf('SELECT DISTINCT %s FROM %s',
                 $this->options['string_page_id_col'],
                 $table
            );
            ++$this->_queries;
            $res = $this->db->GetCol($query);
            if (PEAR::isError($res)) {
                return $res;
            }
            $pages = array_merge($pages, $res);
        }
        /*
        $has_null  = in_array(null, $pages);
        $has_empty = in_array('', $pages);
        $pages = array_unique($pages);
        echo $has_null  = in_array(null, $pages);
        echo $has_empty = in_array('', $pages);
var_dump($pages);
        //if ($has_null && !in_array(null, $pages)) {
        if ($has_null && $has_empty) {
            $pages[] = ''; //empty strings are stripped
        }
        if ($has_empty && !in_array('', $pages)) {
echo 'ADDING VOID';
            $pages[] = '';
        }
echo 'DONE';
        return $pages;
        */
        return array_unique($pages);
    }
    
    // }}}
    // {{{ _tableLangs()

    /**
     * Get table -> language mapping
     *
     * The key of the array is the table that a language is stored in;
     * the value is an /array/ of languages stored in that table.
     *
     * @param   array  $langs  Languages to get mapping for
     * @return  array  Table -> language mapping
     * @access  private
     * @see     Translation2_Container_MDB2::_getLangTable()
     * @author  Ian Eure
     */
    function &_tableLangs($langs)
    {
        $tables = array();
        foreach ($langs as $lang) {
            $table = $this->_getLangTable($lang);
            $tables[$table][] = $lang;
        }
        return $tables;
    }

    // }}}
    // {{{ _getLangTables()

    /**
     * Get tables for languages
     *
     * This is like _getLangTable(), but it returns an array of the tables for
     * multiple languages.
     *
     * @param   array    $langs  Languages to get tables for
     * @return  array
     * @access  private
     * @author  Ian Eure
     */
    function &_getLangTables($langs = null)
    {
        $tables = array();
        $langs = !is_array($langs) ? $this->getLangs('ids') : $langs;
        foreach ($langs as $lang) {
            $tables[] = $this->_getLangTable($lang);
        }
        $tables = array_unique($tables);
        return $tables;
    }

    // }}}
    // {{{ _getLangCols()

    /**
     * Get table columns strings are stored in
     *
     * This is like _getLangCol(), except it returns an array which contains
     * the mapping for multiple languages.
     *
     * @param   array  $langs  Languages to get mapping for
     * @return  array  Language -> column mapping
     * @access  private
     * @see     Translation2_Container_DB::_getLangCol()
     * @author  Ian Eure
     */
    function &_getLangCols($langs)
    {
        $cols = array();
        foreach ($langs as $lang) {
            $cols[$lang] = $this->_getLangCol($lang);
        }
        return $cols;
    }

    // }}}
    // {{{ _recordExists()

    /**
     * Check if there's already a record in the table with the
     * given (pageID, stringID) pair.
     *
     * @param string $stringID
     * @param string $pageID
     * @param string $table
     * @return boolean
     * @access private
     */
    function _recordExists($stringID, $pageID, $table)
    {
        $stringID = $this->db->quote($stringID, 'text');
        $pageID = is_null($pageID) ? ' IS NULL' : ' = ' . $this->db->quote($pageID, 'text');
        $query = sprintf('SELECT COUNT(*) FROM %s WHERE %s=%s AND %s%s',
                         $table,
                         $this->options['string_id_col'],
                         $stringID,
                         $this->options['string_page_id_col'],
                         $pageID
        );
        ++$this->_queries;
        $res = $this->db->GetOne($query);
        if (PEAR::isError($res)) {
            return $res;
        }
        return ($res > 0);
    }

    // }}}
    // {{{ _filterStringsByTable()

    /**
     * Get only the strings for the langs in the given table
     *
     * @param string $pageID
     * @param array  $stringArray Associative array with string translations.
     *               Sample format:  array('en' => 'sample', 'it' => 'esempio')
     * @access private
     */
    function &_filterStringsByTable($stringArray, $table)
    {
        $strings = array();
        foreach ($stringArray as $lang => $string) {
            if ($table == $this->_getLangTable($lang)) {
                $strings[$lang] = $string;
            }
        }
        return $strings;
    }

    // }}}
    // {{{ _getLangsInTable()
    
    /**
     * Get the languages sharing the given table
     *
     * @param string $table table name
     * @return array
     */
    function &_getLangsInTable($table)
    {
        $this->fetchLangs(); // force cache refresh
        $langsInTable = array();
        foreach (array_keys($this->langs) as $lang) {
            if ($table == $this->_getLangTable($lang)) {
                $langsInTable[] = $lang;
            }
        }
        return $langsInTable;
    }
    
    // }}}
}
?>