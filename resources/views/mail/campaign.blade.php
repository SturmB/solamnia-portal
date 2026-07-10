<mjml>
    <mj-head>
        <mj-title>{{ $subject }}</mj-title>
        <mj-attributes>
            <mj-all font-family="'Instrument Sans', Helvetica, Arial, sans-serif" />
            <mj-text font-size="16px" line-height="1.6" color="#1a1a1a" />
        </mj-attributes>
        <mj-style>
            h1, h2, h3 { font-family: 'Marcellus', Georgia, serif; color: #400080; line-height: 1.25; }
            a { color: #400080; }
            img { max-width: 100%; height: auto; }
        </mj-style>
    </mj-head>
    <mj-body background-color="#f4f2f7">
        <mj-section background-color="#400080" padding="24px">
            <mj-column>
                <mj-text align="center" color="#ffffff" font-family="'Marcellus', Georgia, serif" font-size="24px">
                    Solamnia
                </mj-text>
            </mj-column>
        </mj-section>
        <mj-section background-color="#ffffff" padding="32px">
            <mj-column>
                <mj-text>
                    {!! $bodyHtml !!}
                </mj-text>
            </mj-column>
        </mj-section>
        @if ($unsubscribeUrl ?? null)
            <mj-section background-color="#f4f2f7" padding="16px">
                <mj-column>
                    <mj-text align="center" font-size="12px" color="#666666">
                        Trouble viewing this email? <a href="{{ $viewUrl }}">View it in your browser</a>.<br>
                        If you no longer wish to receive these emails, you may <a
                            href="{{ $unsubscribeUrl }}">unsubscribe</a>.
                    </mj-text>
                </mj-column>
            </mj-section>
        @endif
    </mj-body>
</mjml>
