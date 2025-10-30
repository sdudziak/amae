<?php
$res = new stdClass();
$res->status = "success";
$res->html = '<p>Instead of showing a question, show a title/heading. Useful for grouping similar questions together into categories.</p>';
echo json_encode($res);
