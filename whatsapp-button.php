<!-- WhatsApp Floating Button -->
<div id="whatsapp-float" style="position: fixed; bottom: 30px; right: 30px; z-index: 1000;">
    <a href="https://wa.me/919876543210?text=Hello%20Sans%20Digital%20Work%20Support" 
       target="_blank" 
       style="display: flex; align-items: center; justify-content: center; width: 60px; height: 60px; background: #25D366; border-radius: 50%; box-shadow: 0 4px 15px rgba(0,0,0,0.3); text-decoration: none; transition: all 0.3s ease;"
       onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 6px 20px rgba(0,0,0,0.4)'"
       onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 4px 15px rgba(0,0,0,0.3)'">
        <img src="https://upload.wikimedia.org/wikipedia/commons/6/6b/WhatsApp.svg" 
             alt="WhatsApp" 
             style="width: 35px; height: 35px;">
    </a>
</div>

<!-- Tooltip on hover -->
<div id="whatsapp-tooltip" style="position: fixed; bottom: 95px; right: 30px; z-index: 1000; background: #333; color: white; padding: 8px 15px; border-radius: 20px; font-size: 14px; opacity: 0; transition: opacity 0.3s ease; pointer-events: none;">
    Chat with us on WhatsApp!
</div>

<script>
// Show tooltip on hover
document.getElementById('whatsapp-float').addEventListener('mouseenter', function() {
    document.getElementById('whatsapp-tooltip').style.opacity = '1';
});

document.getElementById('whatsapp-float').addEventListener('mouseleave', function() {
    document.getElementById('whatsapp-tooltip').style.opacity = '0';
});
</script>
