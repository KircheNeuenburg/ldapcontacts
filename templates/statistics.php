<?php
// javascript
script( 'ldapcontacts', 'statistics' );
// style
style( 'ldapcontacts', 'statistics' );
?>
<div class="section" id="ldapcontacts">
    <h2><?php p($l->t( 'LDAP Contacts' )); ?></h2>
    
    <script id="ldapcontacts-stat-tpl" type="text/x-handlebars-template">
        <div class="stat">
            <h2 class="title">{{ title }}</h2>
            
            <canvas id="{{ id }}"></canvas>
            
            {{#if total}}
                <h3 class="total"><?php p($l->t( 'Total:' )); ?> {{ total }}</h3>
            {{/if}}
        </div>
    </script>
    
    <div id="ldapcontacts-stats"><div class="icon-loading"></div></div>
</div>