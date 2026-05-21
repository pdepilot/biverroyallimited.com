
'use strict';

/**
 * element toggle function
 */

const elemToggleFunc = function (elem) { elem.classList.toggle("active"); }



/**
 * navbar toggle
 */

const navbar = document.querySelector("[data-navbar]");
const overlay = document.querySelector("[data-overlay]");
const navCloseBtn = document.querySelector("[data-nav-close-btn]");
const navOpenBtn = document.querySelector("[data-nav-open-btn]");
const navbarLinks = document.querySelectorAll("[data-nav-link]");

const navElemArr = [overlay, navCloseBtn, navOpenBtn];

/**
 * close navbar when click on any navbar link
 */

for (let i = 0; i < navbarLinks.length; i++) { navElemArr.push(navbarLinks[i]); }

/**
 * addd event on all elements for toggling navbar
 */

for (let i = 0; i < navElemArr.length; i++) {
  navElemArr[i].addEventListener("click", function () {
    elemToggleFunc(navbar);
    elemToggleFunc(overlay);
  });
}



/**
 * header active state
 */

const header = document.querySelector("[data-header]");

window.addEventListener("scroll", function () {
  window.scrollY >= 400 ? header.classList.add("active")
    : header.classList.remove("active");
}); 


// Background Slideshow with Slide Animations
document.addEventListener('DOMContentLoaded', function() {
  const bgSlides = document.querySelectorAll('.bg-slide');
  const dots = document.querySelectorAll('.slide-dots .dot');
  
  if (bgSlides.length === 0) return;
  
  let currentSlide = 0;
  const slideInterval = 5000; // 5 seconds
  
  // Function to show a specific slide with animation
  function showSlide(index) {
    // Remove active from all slides and dots
    bgSlides.forEach(slide => {
      slide.classList.remove('active', 'slide-in', 'slide-out');
    });
    
    dots.forEach(dot => {
      dot.classList.remove('active');
    });
    
    // Add slide-out animation to current slide if it exists
    if (bgSlides[currentSlide]) {
      bgSlides[currentSlide].classList.add('slide-out');
    }
    
    // Add slide-in animation and active class to new slide
    if (bgSlides[index]) {
      bgSlides[index].classList.add('active', 'slide-in');
    }
    
    // Activate the corresponding dot
    if (dots[index]) {
      dots[index].classList.add('active');
    }
    
    currentSlide = index;
  }
  
  // Function to show next slide
  function nextSlide() {
    let nextIndex = (currentSlide + 1) % bgSlides.length;
    showSlide(nextIndex);
  }
  
  // Start the infinite slideshow
  setInterval(nextSlide, slideInterval);
  
  // Add click events to dots
  dots.forEach((dot, index) => {
    dot.addEventListener('click', () => {
      showSlide(index);
    });
  });
  
  // Initialize first slide
  showSlide(0);
});