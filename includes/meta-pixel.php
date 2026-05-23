<?php
/**
 * Meta Pixel — BIF
 * Place this <?php include __DIR__ . '/includes/meta-pixel.php'; ?> in the <head>
 * of every page where you want Pixel tracking.
 *
 * IMPORTANT: replace 'YOUR_PIXEL_ID_HERE' with the real BIF Meta Pixel ID
 * from Meta Events Manager -> Data Sources -> your pixel.
 */
$BIF_META_PIXEL_ID = getenv('META_PIXEL_ID') ?: '2097706464136548';
?>
<?php if ($BIF_META_PIXEL_ID): ?>
<!-- Meta Pixel Code -->
<script>
!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window, document,'script',
'https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '<?php echo htmlspecialchars($BIF_META_PIXEL_ID); ?>');
fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none"
src="https://www.facebook.com/tr?id=<?php echo htmlspecialchars($BIF_META_PIXEL_ID); ?>&ev=PageView&noscript=1"/></noscript>
<!-- End Meta Pixel Code -->
<?php endif; ?>
