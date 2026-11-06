<?php $pageTitle = 'Notifications'; $treeBase = 'manager';
partial('tree-open', compact('user', 'tenant', 'sub', 'unread', 'notifs', 'pageTitle', 'tree'));
partial('notifications-tree', compact('treeBase', 'notifications'));
partial('app-close'); ?>
