<?php
if (empty($_SESSION['admin_csrf'])) {
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
}
if (empty($_SESSION['block_submission_token'])) {
    $_SESSION['block_submission_token'] = bin2hex(random_bytes(32));
}

$blockFlash = isset($_SESSION['block_flash']) && is_array($_SESSION['block_flash'])
    ? $_SESSION['block_flash']
    : null;
unset($_SESSION['block_flash']);

$blockAccommodations = isset($blockAccommodations) && is_array($blockAccommodations)
    ? $blockAccommodations
    : [];
$blockAction = isset($blockAction) ? (string) $blockAction : 'AdminBlockAccommodation.php';
$blockReturnTo = isset($blockReturnTo) ? (string) $blockReturnTo : 'admin';
?>
<?php if ($blockFlash !== null): ?>
    <p class="block-flash block-flash--<?= ($blockFlash['type'] ?? '') === 'success' ? 'success' : 'error'; ?>"
       role="<?= ($blockFlash['type'] ?? '') === 'success' ? 'status' : 'alert'; ?>">
        <?= htmlspecialchars((string) ($blockFlash['message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
    </p>
<?php endif; ?>

<div class="block-modal" id="block-accommodation-modal" hidden aria-hidden="true">
    <div class="block-modal__overlay" data-block-modal-close="true"></div>
    <section class="block-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="block-modal-title" tabindex="-1">
        <button class="block-modal__close" type="button" aria-label="Close accommodation blocking form" data-block-modal-close="true">&times;</button>
        <p class="section-label">ADMIN ACTION</p>
        <h2 id="block-modal-title">Block accommodation</h2>
        <p class="block-modal__help">A blocked period makes the accommodation unavailable for customer bookings. Existing reservations cannot be overwritten.</p>

        <form method="post" action="<?= htmlspecialchars($blockAction, ENT_QUOTES, 'UTF-8'); ?>" data-block-form>
            <input type="hidden" name="csrf" value="<?= htmlspecialchars((string) $_SESSION['admin_csrf'], ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="submission_token" value="<?= htmlspecialchars((string) $_SESSION['block_submission_token'], ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="return_to" value="<?= htmlspecialchars($blockReturnTo, ENT_QUOTES, 'UTF-8'); ?>">

            <label for="block-huis-id">Accommodation</label>
            <select id="block-huis-id" name="huis_id" required>
                <option value="">Select an accommodation</option>
                <?php foreach ($blockAccommodations as $blockAccommodation): ?>
                    <option value="<?= (int) ($blockAccommodation['Huis_id'] ?? 0); ?>">
                        <?= htmlspecialchars((string) ($blockAccommodation['Huis_naam'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <div class="block-modal__dates">
                <label for="block-start-date">Start date
                    <input id="block-start-date" type="date" name="start_date" min="<?= date('Y-m-d'); ?>" required>
                </label>
                <label for="block-end-date">End date
                    <input id="block-end-date" type="date" name="end_date" min="<?= date('Y-m-d'); ?>" required>
                </label>
            </div>

            <label for="block-reason">Reason <span>(optional)</span></label>
            <textarea id="block-reason" name="reason" maxlength="255" rows="3"></textarea>
            <p class="block-modal__date-help">The end date must be later than the start date.</p>

            <div class="block-modal__actions">
                <button class="block-modal__cancel" type="button" data-block-modal-close="true">CANCEL</button>
                <button class="block-modal__confirm" type="submit">BLOCK HOUSE</button>
            </div>
        </form>
    </section>
</div>
