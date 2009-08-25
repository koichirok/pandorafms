<?php
/**
 * @package Include/help/en
 */
?>
<h1>Post process</h1>

Post process is a numeric value used after get data to numerical post process in a multiplier operation. For example a data with a value of 1000 with a Post Process value of 1024 will result in a definitive data of 1024000 value. This is useful to normalize data, convert between units, etc. This also can be used to divide, using a multiplier below 1 value, like, for example, 0.001 that will divide current value by 1000.
<br /><br />
An empty value, or 0, will disable the usage of post process (default).
