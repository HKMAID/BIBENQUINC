<?php
/**
 * Entry point for InfinityFree hosting.
 * The document root points to the project root, so we redirect everything to public/.
 */
header('Location: /public/index.php', true, 301);
exit;
