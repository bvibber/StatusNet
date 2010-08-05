<?php
/*
 * StatusNet - the distributed open-source microblogging tool
 * Copyright (C) 2010, StatusNet, Inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * @package FeedMirrorPlugin
 * @maintainer Brion Vibber <brion@status.net>
 */

class FeedMirror extends Memcached_DataObject
{
    public $__table = 'feedmirror';

    public $id;

    public $uri; // feed URI; links to feedsub.uri
    public $profile_id; // local user to post under

    public $style; // "link", "summary", "body"

    public $created;
    public $modified;

    public /*static*/ function staticGet($k, $v=null)
    {
        return parent::staticGet(__CLASS__, $k, $v);
    }

    /**
     * return table definition for DB_DataObject
     *
     * DB_DataObject needs to know something about the table to manipulate
     * instances. This method provides all the DB_DataObject needs to know.
     *
     * @return array array of column definitions
     */

    function table()
    {
        return array('id' => DB_DATAOBJECT_INT + DB_DATAOBJECT_NOTNULL,

                     'uri' => DB_DATAOBJECT_STR + DB_DATAOBJECT_NOTNULL,
                     'profile_id' =>  DB_DATAOBJECT_INT + DB_DATAOBJECT_NOTNULL,

                     'style' =>  DB_DATAOBJECT_STR,

                     'created' => DB_DATAOBJECT_STR + DB_DATAOBJECT_DATE + DB_DATAOBJECT_TIME + DB_DATAOBJECT_NOTNULL,
                     'modified' => DB_DATAOBJECT_STR + DB_DATAOBJECT_DATE + DB_DATAOBJECT_TIME + DB_DATAOBJECT_NOTNULL);
    }

    static function schemaDef()
    {
        // @fixme keys
        return array(new ColumnDef('id', 'integer',
                                   /*size*/ null,
                                   /*nullable*/ false,
                                   /*key*/ 'PRI',
                                   /*default*/ null,
                                   /*extra*/ null,
                                   /*auto_increment*/ true),

                     new ColumnDef('uri', 'varchar',
                                   255, false),
                     new ColumnDef('profile_id', 'integer',
                                   null, false),

                     new ColumnDef('created', 'datetime',
                                   null, false),
                     new ColumnDef('modified', 'datetime',
                                   null, false));
    }

    /**
     * return key definitions for DB_DataObject
     *
     * DB_DataObject needs to know about keys that the table has; this function
     * defines them.
     *
     * @return array key definitions
     */

    function keys()
    {
        return array_keys($this->keyTypes());
    }

    /**
     * return key definitions for Memcached_DataObject
     *
     * Our caching system uses the same key definitions, but uses a different
     * method to get them.
     *
     * @return array key definitions
     */

    function keyTypes()
    {
        // @fixme keys
        return array('id' => 'K');
    }

    function sequenceKey()
    {
        return array('id', true, false);
    }

    /**
     * Fetch the StatusNet-side profile for this feed
     * @return Profile
     */
    public function localProfile()
    {
        if ($this->profile_id) {
            return Profile::staticGet('id', $this->profile_id);
        }
        return null;
    }

    /**
     * @return FeedSub
     * @throws FeedSubException if feed is invalid or lacks PuSH setup
     */
    public function ensureFeed()
    {
        return FeedSub::ensureFeed($this->uri);
    }

}

