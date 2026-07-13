<mjml>
    <mj-head>
        <mj-title>{{ $subject }}</mj-title>
        {{-- One Fontshare link for both families (DESIGN.md); most clients ignore it and fall back. --}}
        <mj-font name="Clash Display"
            href="https://api.fontshare.com/v2/css?f[]=clash-display@600,700&f[]=satoshi@400,500,700&display=swap" />
        <mj-attributes>
            <mj-all font-family="Satoshi, 'Avenir Next', system-ui, sans-serif" />
            <mj-text font-size="16px" line-height="1.6" color="#edf2f8" padding="0" />
            <mj-image padding="12px 0" />
            <mj-section background-color="#010813" padding="8px 32px" />
        </mj-attributes>
        <mj-style>
            h1, h2, h3 { font-family: 'Clash Display', 'Avenir Next', sans-serif; color: #f6f9fc; line-height: 1.25;
            letter-spacing: -0.01em; }
            a { color: #cbaaff; }
            img { max-width: 100%; height: auto; border-radius: 10px; }
            {{-- Pre-baked echo of the aurora shader (never the shader itself): curtain
                 radials under two faint ray stripings, over a grounding fade. Clients
                 without <style> support keep the solid night the section carries inline. --}}
            .sky-band > table {
            background:
            linear-gradient(180deg, rgba(2, 7, 19, 0) 42%, rgba(2, 7, 19, 0.78)),
            repeating-linear-gradient(94deg,
            rgba(181, 139, 249, 0) 0 26px, rgba(181, 139, 249, 0.07) 29px 33px, rgba(181, 139, 249, 0) 36px 58px),
            repeating-linear-gradient(86deg,
            rgba(65, 220, 165, 0) 0 34px, rgba(65, 220, 165, 0.05) 38px 42px, rgba(65, 220, 165, 0) 46px 73px),
            radial-gradient(480px 256px at 12% -30%,
            rgba(0, 220, 152, 0.65), rgba(0, 130, 82, 0.38) 46%, rgba(0, 130, 82, 0) 68%),
            radial-gradient(544px 288px at 60% -40%,
            rgba(162, 100, 246, 0.75), rgba(100, 45, 164, 0.45) 50%, rgba(100, 45, 164, 0) 70%),
            radial-gradient(384px 208px at 100% -20%,
            rgba(223, 106, 166, 0.55), rgba(132, 45, 92, 0.32) 48%, rgba(132, 45, 92, 0) 70%),
            #030915 !important;
            }
        </mj-style>
    </mj-head>
    <mj-body background-color="#00030c">
        <mj-section css-class="sky-band" background-color="#030915" padding="36px 32px 30px">
            <mj-column>
                <mj-text font-family="'Clash Display', 'Avenir Next', sans-serif" font-size="18px" font-weight="600"
                    color="#edf2f8" padding="0">
                    Solamnia
                </mj-text>
                <mj-text font-family="'Clash Display', 'Avenir Next', sans-serif" font-size="30px" font-weight="600"
                    color="#f6f9fc" line-height="1.2" padding="22px 0 0">
                    {{ $subject }}
                </mj-text>
            </mj-column>
        </mj-section>
        {!! $bodyMjml !!}
        @if ($unsubscribeUrl ?? null)
            <mj-section border-top="1px solid #1b1f30" padding="18px 32px 30px">
                <mj-column>
                    <mj-text align="center" font-size="12px" color="#a2acb7">
                        Trouble viewing this email? <a href="{{ $viewUrl }}">View it in your browser</a>.<br>
                        If you no longer wish to receive these emails, you may <a
                            href="{{ $unsubscribeUrl }}">unsubscribe</a>.
                    </mj-text>
                </mj-column>
            </mj-section>
        @endif
    </mj-body>
</mjml>
