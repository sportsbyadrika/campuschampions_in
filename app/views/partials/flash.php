<?php
use App\Core\Flash;
$messages = Flash::pull();
if (!empty($messages)):
?>
<script>
    window.__flash = <?= json_encode(array_map(fn($t, $m) => ['type' => $t, 'message' => $m], array_keys($messages), $messages)) ?>;
</script>
<?php endif; ?>
