<?php $pageTitle = 'Approvals'; $treeBase = 'employee'; $history = [];
partial('tree-open', compact('user', 'tenant', 'sub', 'unread', 'notifs', 'pageTitle', 'tree'));
partial('approvals-tree', compact('treeBase', 'inbox', 'mine', 'history', 'rows', 'workflows', 'tenantUsers'));
partial('app-close'); ?>
