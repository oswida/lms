<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2013 LMS Developers
 *
 *  Please, see the doc/AUTHORS for more information about authors!
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License Version 2 as
 *  published by the Free Software Foundation.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
 *  USA.
 *
 *  $Id$
 */

/**
 * UseraddHandler
 *
 * @author Maciej Lew <maciej.lew.1987@gmail.com>
 */
class UseraddHandler
{
    /**
     * Example of additional validation
     * 
     * @param mixed $hook_data
     * @return mixed
     */
    public function useraddValidationBeforeSubmit($hook_data)
    {
        if (strpos($hook_data['useradd']['name'], 'X') !== 0) {
            $hook_data['error']['name'] = 'Name must start with "X" ;-)';
        }
        return $hook_data['error'];
    }
    
    /**
     * Example of additional logging
     * 
     * @param mixed $hook_data
     * @return null
     */
    public function useraddAfterSubmit($hook_data)
    {
        error_log("User with id $hook_data added");
        return null;
    }
    
}
