<?php

OCP\User::checkAdminUser();

$tmpl = new OCP\Template('hsldapcontacts', 'settings');

return $tmpl->fetchPage();
