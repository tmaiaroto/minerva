<?php $this->title('Missing Dependencies'); ?>
<h1>Missing Dependencies</h1>
<p>
You are missing the following libraries that Minerva depends on:
</p>
<p>
    <ul>
<?php
foreach($missing_deps as $k => $v) {
    echo '<li>';
    echo (is_numeric($k)) ? $v:$k . ' (' . $this->html->link($v, $v, array('target' => '_blank')) . ')';
    echo '</li>';
}
?>
    </ul>
</p>
<p>
    You will need to ensure that you have these libraries yourself for now, but in the future there will hopefully be an option to automatically download and install them.
</p>