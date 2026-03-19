</div> <footer class="main-footer">
        <div class="footer-content">
            <p>&copy; <?php echo date("Y"); ?> <strong>Elvis Midega Beauty Salon</strong>. All Rights Reserved.</p>
            <p class="credits">System Developed for Excellence.</p>
        </div>
    </footer>

    <script src="../assets/js/script.js"></script>

    <style>
        .main-footer {
            text-align: center;
            padding: 30px 20px;
            margin-top: 60px;
            border-top: 1px solid var(--border-color);
            color: var(--text-muted);
            background: var(--card-bg); /* Subtle blend with page cards */
            transition: background 0.3s, color 0.3s;
        }

        .footer-content p {
            margin: 5px 0;
            font-size: 0.95rem;
        }

        .credits {
            font-size: 0.8rem;
            opacity: 0.7;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        /* Responsive adjustments for mobile */
        @media (max-width: 600px) {
            .main-footer {
                padding: 20px 15px;
                margin-top: 40px;
            }
            .footer-content p {
                font-size: 0.85rem;
            }
        }
    </style>

</body>
</html>