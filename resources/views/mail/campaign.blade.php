<mjml>
    @include('mail.partials.head', ['title' => $subject])
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
