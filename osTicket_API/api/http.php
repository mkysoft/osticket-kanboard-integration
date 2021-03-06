<?php
/*********************************************************************
    http.php

    HTTP controller for the osTicket API

    Jared Hancock
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
// Use sessions — it's important for SSO authentication, which uses
// /api/auth/ext
define('DISABLE_SESSION', false);

require 'api.inc.php';

# Include the main api urls
require_once INCLUDE_DIR."class.dispatcher.php";

$dispatcher = patterns('',
        url_post("^/tickets\.(?P<format>xml|json|email)$", array('api.tickets.php:TicketApiController','create')),
        url_get("^/tasks\.(?P<format>xml|json)$", array('api.tasks.php:TaskApiController','report')),
        url_get("^/task\.(?P<format>xml|json)/(?P<id>\d+)$", array('api.tasks.php:TaskApiController','get')),
        url_get("^/task\.(?P<format>xml|json)/(?P<id>\d+)/title$", array('api.tasks.php:TaskApiController','getTitle')),
        url_get("^/threads\.(?P<format>xml|json)$", array('api.threads.php:ThreadApiController','report')),
        url_get("^/thread\.(?P<format>xml|json)/(?P<id>\d+)$", array('api.threads.php:ThreadApiController','get')),
        url_get("^/thread\.(?P<format>xml|json)/(?P<id>\d+)/entries$", array('api.threads.php:ThreadApiController','getEntries')),
        url('^/tasks/', patterns('',
                url_post("^cron$", array('api.cron.php:CronApiController', 'execute'))
         ))
        );

Signal::send('api', $dispatcher);

# Call the respective function
print $dispatcher->resolve($ost->get_path_info());
?>
