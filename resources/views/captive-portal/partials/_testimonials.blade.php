{{-- Only renders anything if the tenant actually filled in a real testimonial — no fabricated
     "5000+ happy customers" copy. Shared across templates so any of them can @include it. --}}
@if($portal?->testimonial_1_text || $portal?->testimonial_2_text)
    <div class="testimonials">
        @if($portal->testimonial_1_text)
            <div class="testimonial">
                <div class="quote">&ldquo;{{ $portal->testimonial_1_text }}&rdquo;</div>
                @if($portal->testimonial_1_author)
                    <div class="author">{{ $portal->testimonial_1_author }}</div>
                @endif
            </div>
        @endif
        @if($portal->testimonial_2_text)
            <div class="testimonial">
                <div class="quote">&ldquo;{{ $portal->testimonial_2_text }}&rdquo;</div>
                @if($portal->testimonial_2_author)
                    <div class="author">{{ $portal->testimonial_2_author }}</div>
                @endif
            </div>
        @endif
    </div>
@endif
