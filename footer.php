<footer>BedTrack — hospital bed booking, built for the moment it matters.</footer>
<script src="<?php echo BASE_URL; ?>/assets/js/script.js?v=<?php echo time(); ?>"></script>
<script src="<?php echo BASE_URL; ?>/assets/js/validation.js?v=<?php echo time(); ?>"></script>
<?php if (!empty($extraJs)) foreach ($extraJs as $js): ?>
<script src="<?php echo BASE_URL; ?>/assets/js/<?php echo esc($js); ?>?v=<?php echo time(); ?>"></script>
<?php endforeach; ?>
</body>
</html>
