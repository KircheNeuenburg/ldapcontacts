<?php

OCP\User::checkAdminUser();

$tmpl = new OCP\Template('ldapcontacts', 'settings');

return $tmpl->fetchPage();
