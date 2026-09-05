<mjml>
    @include('mail.partials.head', ['title' => "You're invited to Solamnia"])
    <mj-body background-color="#00030c">
        <mj-section css-class="sky-band" background-color="#030915" padding="36px 32px 30px">
            <mj-column>
                <mj-text font-family="'Clash Display', 'Avenir Next', sans-serif" font-size="18px" font-weight="600"
                    color="#edf2f8" padding="0">
                    Solamnia
                </mj-text>
                <mj-text font-family="'Clash Display', 'Avenir Next', sans-serif" font-size="30px" font-weight="600"
                    color="#f6f9fc" line-height="1.2" padding="22px 0 0">
                    You're invited
                </mj-text>
            </mj-column>
        </mj-section>
        <mj-section padding="8px 32px 36px">
            <mj-column>
                <mj-text>
                    {{ $inviterName }} has invited you to join Solamnia. Accept the invite to
                    choose your username and take your seat.
                </mj-text>
                {{-- Violet fill with night-deep text (DESIGN.md): white-on-violet fails AA. --}}
                <mj-button href="{{ $acceptUrl }}" background-color="#a264f6" color="#050a17" border-radius="999px"
                    font-weight="700" padding="24px 0 8px">
                    Accept your invite
                </mj-button>
                {{-- The plain URL survives clients that strip buttons. --}}
                <mj-text font-size="13px" color="#a2acb7">
                    If the button doesn't work, paste this link into your browser:<br>
                    <a href="{{ $acceptUrl }}">{{ $acceptUrl }}</a>
                </mj-text>
                <mj-text font-size="13px" color="#a2acb7" padding-top="16px">
                    This invite expires on {{ $expiresAt->format('F j, Y') }}.
                </mj-text>
            </mj-column>
        </mj-section>
    </mj-body>
</mjml>
