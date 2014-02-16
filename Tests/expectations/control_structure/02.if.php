<?php if ($__lv['a']): ?>
hello a
<?php elseif ($__lv['b']): ?>
hello b
<?php else: ?>
not hello
<?php endif; ?>

<?php if (strtolower($__lv['user']['name']) == "sasha"): ?>
ok
<?php endif; ?>

<?php if ((($__lv['a'] || $__lv['b']) && $__lv['c']->d() || $__lv['e']['d'])): ?>
ok
<?php endif; ?>