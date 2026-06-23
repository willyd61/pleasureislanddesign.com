/**
 * Custom Lightbox System — Zero Dependencies
 * Handles gallery item display with before/after slider support
 * Performance-optimized: CSS transforms, 60fps animations
 */

class GalleryLightbox {
  constructor(options = {}) {
    this.currentIndex = 0;
    this.items = [];
    this.isOpen = false;
    this.galleryData = options.galleryData || [];

    this.init();
  }

  init() {
    this.createLightboxMarkup();
    this.bindEvents();
    this.loadGalleryData();
  }

  createLightboxMarkup() {
    const lightboxHTML = `
      <div class="lightbox-overlay" id="lightbox-overlay">
        <div class="lightbox-container" role="dialog" aria-modal="true" aria-labelledby="lightbox-title">
          <button class="lightbox-close" aria-label="Close lightbox">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>

          <div class="lightbox-content">
            <div class="lightbox-image-wrapper" id="lightbox-image-wrapper">
              <!-- Before/after slider will be injected here -->
              <img id="lightbox-image" src="" alt="" loading="lazy">
            </div>

            <div class="lightbox-metadata">
              <h2 id="lightbox-title" class="lightbox-title"></h2>
              <p class="lightbox-location"></p>
              <p class="lightbox-service"></p>
              <p class="lightbox-testimonial" style="font-style: italic; color: rgba(255,255,255,0.7); margin-top: 1rem;"></p>
            </div>
          </div>

          <div class="lightbox-controls">
            <button class="lightbox-prev" aria-label="Previous image">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
              </svg>
            </button>

            <div class="lightbox-counter">
              <span id="lightbox-current">1</span> / <span id="lightbox-total">1</span>
            </div>

            <button class="lightbox-next" aria-label="Next image">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="9 18 15 12 9 6"></polyline>
              </svg>
            </button>
          </div>
        </div>
      </div>
    `;

    document.body.insertAdjacentHTML('beforeend', lightboxHTML);
  }

  bindEvents() {
    const overlay = document.getElementById('lightbox-overlay');
    const closeBtn = document.querySelector('.lightbox-close');
    const prevBtn = document.querySelector('.lightbox-prev');
    const nextBtn = document.querySelector('.lightbox-next');
    const galleryItems = document.querySelectorAll('[data-lightbox]');

    // Open lightbox
    galleryItems.forEach((item, index) => {
      item.addEventListener('click', (e) => {
        e.preventDefault();
        this.currentIndex = index;
        this.open();
      });

      // Keyboard support
      item.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          this.currentIndex = index;
          this.open();
        }
      });
    });

    // Close lightbox
    closeBtn?.addEventListener('click', () => this.close());
    overlay?.addEventListener('click', (e) => {
      if (e.target === overlay) {this.close();}
    });

    // Navigation
    prevBtn?.addEventListener('click', () => this.prev());
    nextBtn?.addEventListener('click', () => this.next());

    // Keyboard navigation
    document.addEventListener('keydown', (e) => {
      if (!this.isOpen) {return;}
      if (e.key === 'ArrowLeft') {this.prev();}
      if (e.key === 'ArrowRight') {this.next();}
      if (e.key === 'Escape') {this.close();}
    });

    // Touch navigation
    let touchStartX = 0;
    overlay?.addEventListener('touchstart', (e) => {
      touchStartX = e.touches[0].clientX;
    });
    overlay?.addEventListener('touchend', (e) => {
      const touchEndX = e.changedTouches[0].clientX;
      if (touchStartX - touchEndX > 50) {this.next();}
      if (touchEndX - touchStartX > 50) {this.prev();}
    });
  }

  loadGalleryData() {
    const items = document.querySelectorAll('[data-lightbox]');
    this.items = Array.from(items).map((item) => ({
      title: item.dataset.title || '',
      location: item.dataset.location || '',
      service: item.dataset.service || '',
      beforeImage: item.dataset.before || '',
      afterImage: item.dataset.after || '',
      testimonial: item.dataset.testimonial || '',
      element: item,
    }));

    const totalSpan = document.getElementById('lightbox-total');
    if (totalSpan) {totalSpan.textContent = this.items.length;}
  }

  open() {
    this.isOpen = true;
    const overlay = document.getElementById('lightbox-overlay');
    overlay?.classList.add('active');
    document.body.style.overflow = 'hidden';
    this.display();

    // Focus on close button for accessibility
    document.querySelector('.lightbox-close')?.focus();
  }

  close() {
    this.isOpen = false;
    const overlay = document.getElementById('lightbox-overlay');
    overlay?.classList.remove('active');
    document.body.style.overflow = '';
  }

  display() {
    const item = this.items[this.currentIndex];
    if (!item) {return;}

    const imageWrapper = document.getElementById('lightbox-image-wrapper');
    const image = document.getElementById('lightbox-image');
    const title = document.getElementById('lightbox-title');
    const location = document.querySelector('.lightbox-location');
    const service = document.querySelector('.lightbox-service');
    const testimonial = document.querySelector('.lightbox-testimonial');
    const current = document.getElementById('lightbox-current');

    // Update metadata
    if (title) {title.textContent = item.title;}
    if (location) {location.textContent = item.location;}
    if (service) {service.textContent = item.service;}
    if (testimonial) {testimonial.textContent = item.testimonial;}
    if (current) {current.textContent = this.currentIndex + 1;}

    // Handle before/after slider
    if (item.beforeImage && item.afterImage) {
      this.createBeforeAfterSlider(imageWrapper, item.beforeImage, item.afterImage);
    } else {
      image.src = item.afterImage || item.beforeImage;
      image.alt = item.title;
    }
  }

  createBeforeAfterSlider(wrapper, beforeSrc, afterSrc) {
    wrapper.innerHTML = `
      <div class="lightbox-before-after-slider" data-before="${beforeSrc}" data-after="${afterSrc}">
        <div class="slider-after"><img src="${afterSrc}" alt="After" loading="lazy"></div>
        <div class="slider-before"><img src="${beforeSrc}" alt="Before" loading="lazy"></div>
        <div class="slider-handle" role="slider" aria-label="Drag to compare before and after" aria-valuemin="0" aria-valuemax="100" aria-valuenow="50" tabindex="0">
          <span class="slider-arrow slider-arrow-left">◀</span>
          <span class="slider-arrow slider-arrow-right">▶</span>
        </div>
      </div>
    `;

    this.initBeforeAfterSlider(wrapper.querySelector('.lightbox-before-after-slider'));
  }

  initBeforeAfterSlider(sliderElement) {
    const handle = sliderElement.querySelector('.slider-handle');
    const before = sliderElement.querySelector('.slider-before');
    let isActive = false;

    const updateSlider = (e) => {
      if (!isActive) {return;}

      const rect = sliderElement.getBoundingClientRect();
      let x = e.clientX - rect.left;
      if (e.touches) {x = e.touches[0].clientX - rect.left;}

      x = Math.max(0, Math.min(x, rect.width));
      const percentage = (x / rect.width) * 100;

      before.style.clipPath = `inset(0 ${100 - percentage}% 0 0)`;
      handle.style.left = `${percentage}%`;
      handle.setAttribute('aria-valuenow', Math.round(percentage));
    };

    handle.addEventListener('mousedown', () => { isActive = true; });
    handle.addEventListener('touchstart', () => { isActive = true; });
    document.addEventListener('mousemove', updateSlider);
    document.addEventListener('touchmove', updateSlider);
    document.addEventListener('mouseup', () => { isActive = false; });
    document.addEventListener('touchend', () => { isActive = false; });

    // Keyboard support
    handle.addEventListener('keydown', (e) => {
      const rect = sliderElement.getBoundingClientRect();
      let currentX = (parseInt(handle.style.left) || 50) / 100 * rect.width;

      if (e.key === 'ArrowLeft') {currentX -= 10;}
      if (e.key === 'ArrowRight') {currentX += 10;}

      const mouseEvent = new MouseEvent('mousemove', {
        clientX: rect.left + currentX,
      });
      updateSlider(mouseEvent);
    });
  }

  prev() {
    this.currentIndex = (this.currentIndex - 1 + this.items.length) % this.items.length;
    this.display();
  }

  next() {
    this.currentIndex = (this.currentIndex + 1) % this.items.length;
    this.display();
  }
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
  if (document.querySelectorAll('[data-lightbox]').length > 0) {
    window.galleryLightbox = new GalleryLightbox();
  }
});
