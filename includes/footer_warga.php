    </main>

    <!-- BottomNavBar -->
    <nav class="fixed bottom-0 w-full z-50 bg-surface border-t border-outline-variant md:hidden">
        <div class="flex justify-around items-center h-[64px] px-2 w-full max-w-[600px] mx-auto">
            <a href="warga_pengumuman.php" class="flex flex-col items-center justify-center <?= ($active_tab=='pengumuman') ? 'text-primary font-bold' : 'text-on-surface-variant' ?> hover:bg-surface-container-low transition-colors w-16 h-full rounded-xl">
                <span class="material-symbols-outlined mb-1">campaign</span>
                <span class="text-[10px]">Info</span>
            </a>
            <a href="warga_kegiatan.php" class="flex flex-col items-center justify-center <?= ($active_tab=='kegiatan') ? 'text-primary font-bold' : 'text-on-surface-variant' ?> hover:bg-surface-container-low transition-colors w-16 h-full rounded-xl">
                <span class="material-symbols-outlined mb-1">event</span>
                <span class="text-[10px]">Kegiatan</span>
            </a>
            <a href="warga_iuran.php" class="flex flex-col items-center justify-center <?= ($active_tab=='iuran') ? 'text-primary font-bold' : 'text-on-surface-variant' ?> hover:bg-surface-container-low transition-colors w-16 h-full rounded-xl">
                <span class="material-symbols-outlined mb-1">payments</span>
                <span class="text-[10px]">Iuran</span>
            </a>
            <a href="warga_surat.php" class="flex flex-col items-center justify-center <?= ($active_tab=='surat') ? 'text-primary font-bold' : 'text-on-surface-variant' ?> hover:bg-surface-container-low transition-colors w-16 h-full rounded-xl">
                <span class="material-symbols-outlined mb-1">description</span>
                <span class="text-[10px]">Surat</span>
            </a>
            <a href="warga_aspirasi.php" class="flex flex-col items-center justify-center <?= ($active_tab=='aspirasi') ? 'text-primary font-bold' : 'text-on-surface-variant' ?> hover:bg-surface-container-low transition-colors w-16 h-full rounded-xl">
                <span class="material-symbols-outlined mb-1">forum</span>
                <span class="text-[10px]">Aspirasi</span>
            </a>
        </div>
    </nav>
</body>
</html>
