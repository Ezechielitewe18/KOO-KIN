        </main>
        <footer class="adm-pied">
            <span>© <?= date('Y') ?> KOO-KIN — Administration</span>
            <span><?= e((string) param('site_slogan', 'Cuisine Congolaise Authentique')) ?></span>
        </footer>
    </div>
</div>

<div class="adm-overlay" id="adm-overlay"></div>
<script src="<?= e(admin_url('assets/js/admin.js')) ?>" defer></script>
</body>
</html>
