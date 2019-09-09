<div class="google-search-api-widget">
    <form class="form google-search-api-form" method="post" enctype="application/x-www-form-urlencoded">
        <input type="hidden" name="<?php echo $csrfName; ?>" value="<?php echo $csrf; ?>">
        <div class="form-fields">
            <div class="form-field required">
                <label>
                    <span class="form-field--label">Keywords:</span>
                    <input class="form-field--value" type="text" id="keywords" name="keywords" value="<?php echo $keywords; ?>" required>
                </label>
            </div>
            <div class="form-field required">
                <label>
                    <span class="form-field--label">URL:</span>
                    <input class="form-field--value" type="text" id="url" name="url" value="<?php echo $url; ?>" required>
                </label>
            </div>
        </div>
        <div class="form-buttons">
            <button class="button form-submit" type="submit" value="Search">Search<span class="hidden"></span></button>
        </div>
    </form>
    <div class="message-container<?php if (empty($appearances)): ?> hidden<?php endif; ?>">
        <ul class="message-list">
            <?php if (!empty($appearances)): ?>
                <li><?php echo implode(',', $appearances); ?></li>
            <?php endif; ?>
        </ul>
    </div>
</div>
