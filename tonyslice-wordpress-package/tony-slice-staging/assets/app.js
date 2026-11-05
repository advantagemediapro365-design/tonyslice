const GA_MEASUREMENT_ID = "G-YY4PJJH0J4";
const CONSENT_KEY = "tonyslice-analytics-consent";

const menuToggle = document.querySelector(".menu-toggle");
const primaryNav = document.querySelector("#primary-nav");
const consentBanner = document.querySelector("#consent-banner");
const year = document.querySelector("#year");

const stagingPage = document.querySelector(".tony-slice-staging-page");
if (stagingPage?.dataset.homeUrl) {
  const stagingHome = new URL(stagingPage.dataset.homeUrl, window.location.href);
  stagingPage.querySelectorAll('a[href^="/"]').forEach((link) => {
    const target = new URL(link.href, window.location.href);
    if (target.origin === stagingHome.origin && (target.pathname === "/" || target.pathname === "/index.html")) {
      target.pathname = stagingHome.pathname;
      link.href = target.href;
    }
  });
}

if (year) year.textContent = new Date().getFullYear();

const seasonLabel = document.querySelector("#season-label");
const holidayRibbon = document.querySelector(".holiday-ribbon");
if (seasonLabel && holidayRibbon) {
  const dateParts = Object.fromEntries(new Intl.DateTimeFormat("en-US", {
    timeZone: "America/New_York",
    year: "numeric",
    month: "2-digit",
    day: "2-digit"
  }).formatToParts(new Date()).map(({ type, value }) => [type, value]));
  const newYorkDate = `${dateParts.year}-${dateParts.month}-${dateParts.day}`;
  const seasonOverride = new URLSearchParams(window.location.search).get("season");
  const holidayEdition = seasonOverride === "holiday" || (seasonOverride !== "evergreen" && newYorkDate >= "2026-10-23" && newYorkDate <= "2027-01-15");
  seasonLabel.textContent = holidayEdition ? "THE HOLIDAY EDITION" : "TONY SLICE";
  holidayRibbon.hidden = !holidayEdition;
}

function track(eventName, label) {
  if (localStorage.getItem(CONSENT_KEY) !== "granted" || typeof window.gtag !== "function") return;
  window.gtag("event", eventName, { event_category: "engagement", event_label: label });
}

document.querySelectorAll("[data-track]").forEach((element) => {
  element.addEventListener("click", () => {
    track(element.dataset.track, element.dataset.label || element.textContent.trim());
  });
});

function loadAnalytics() {
  if (window.gtag) return;
  window.dataLayer = window.dataLayer || [];
  window.gtag = function gtag() { window.dataLayer.push(arguments); };
  window.gtag("js", new Date());
  window.gtag("config", GA_MEASUREMENT_ID);
  const analyticsScript = document.createElement("script");
  analyticsScript.async = true;
  analyticsScript.src = `https://www.googletagmanager.com/gtag/js?id=${GA_MEASUREMENT_ID}`;
  document.head.append(analyticsScript);
}

if (menuToggle && primaryNav) {
  menuToggle.addEventListener("click", () => {
    const isExpanded = menuToggle.getAttribute("aria-expanded") === "true";
    menuToggle.setAttribute("aria-expanded", String(!isExpanded));
    menuToggle.setAttribute("aria-label", isExpanded ? "Open navigation" : "Close navigation");
    primaryNav.classList.toggle("is-open", !isExpanded);
  });

  primaryNav.querySelectorAll("a").forEach((link) => {
    link.addEventListener("click", () => {
      menuToggle.setAttribute("aria-expanded", "false");
      menuToggle.setAttribute("aria-label", "Open navigation");
      primaryNav.classList.remove("is-open");
    });
  });
}

if (!localStorage.getItem(CONSENT_KEY) && consentBanner) {
  consentBanner.hidden = false;
}

document.querySelector("#consent-accept")?.addEventListener("click", () => {
  localStorage.setItem(CONSENT_KEY, "granted");
  consentBanner.hidden = true;
  loadAnalytics();
});

document.querySelector("#consent-reject")?.addEventListener("click", () => {
  localStorage.setItem(CONSENT_KEY, "denied");
  consentBanner.hidden = true;
});

document.querySelectorAll("[data-video]").forEach((button) => {
  button.addEventListener("click", () => {
    const videoId = button.dataset.video;
    const frame = document.createElement("iframe");
    frame.className = "highlight-embed";
    frame.title = "Tony Slice highlight video";
    frame.allow = "accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share";
    frame.allowFullscreen = true;
    frame.referrerPolicy = "strict-origin-when-cross-origin";
    frame.src = `https://www.youtube-nocookie.com/embed/${encodeURIComponent(videoId)}?autoplay=1&rel=0`;
    button.replaceWith(frame);
    track("video_click", "hero_highlight_play");
  });
});

const bookingForm = document.querySelector("#booking-form");
const formStatus = document.querySelector("#form-status");

if (bookingForm) {
  const serviceSelect = bookingForm.querySelector('select[name="service"]');
  const emailInput = bookingForm.querySelector('input[name="email"]');
  const phoneInput = bookingForm.querySelector('input[name="phone"]');
  const serviceDetails = document.querySelectorAll("[data-service-details]");

  function updateServiceDetails() {
    const service = serviceSelect.value;
    const showEventDetails = /Event|Party|Corporate/.test(service);
    const showHotelDetails = service === "Hotel collaboration";
    const showCampaignDetails = /Social content visit|Monthly social management|Launch campaign/.test(service);

    serviceDetails.forEach((section) => {
      const type = section.dataset.serviceDetails;
      section.hidden = type === "event" ? !showEventDetails : type === "hotel" ? !showHotelDetails : !showCampaignDetails;
    });
  }

  function validateContact() {
    const hasContact = Boolean(emailInput.value.trim() || phoneInput.value.trim());
    const message = hasContact ? "" : "Please enter an email address or phone number.";
    emailInput.setCustomValidity(message);
    phoneInput.setCustomValidity(message);
  }

  serviceSelect?.addEventListener("change", updateServiceDetails);
  emailInput?.addEventListener("input", validateContact);
  phoneInput?.addEventListener("input", validateContact);
  const requestedService = new URLSearchParams(window.location.search).get("service");
  if (requestedService && Array.from(serviceSelect.options).some((option) => option.value === requestedService)) {
    serviceSelect.value = requestedService;
  }
  updateServiceDetails();
  validateContact();
}

document.querySelectorAll("[data-prefill-service]").forEach((link) => {
  link.addEventListener("click", () => {
    if (!bookingForm) return;
    const serviceSelect = bookingForm.querySelector('select[name="service"]');
    serviceSelect.value = link.dataset.prefillService;
    serviceSelect.dispatchEvent(new Event("change"));
  });
});

bookingForm?.addEventListener("submit", async (event) => {
  event.preventDefault();
  const emailInput = bookingForm.querySelector('input[name="email"]');
  const phoneInput = bookingForm.querySelector('input[name="phone"]');
  if (emailInput && phoneInput) {
    const message = emailInput.value.trim() || phoneInput.value.trim() ? "" : "Please enter an email address or phone number.";
    emailInput.setCustomValidity(message);
    phoneInput.setCustomValidity(message);
  }
  if (!bookingForm.reportValidity()) return;

  const submitButton = bookingForm.querySelector('button[type="submit"]');
  submitButton.disabled = true;
  formStatus.textContent = "Sending your request…";
  formStatus.classList.remove("form-status-error");

  try {
    const response = await fetch(bookingForm.action, {
      method: "POST",
      body: new FormData(bookingForm),
      headers: { Accept: "application/json" }
    });
    const result = await response.json();

    if (!response.ok || (result.ok !== true && result.success !== true)) {
      throw new Error(result.message || "We couldn’t send your request. Please try again or call/text 570-494-7523.");
    }

    track("lead_form_success", "inquiry_request");
    track("booking_form_success", "booking_request");
    bookingForm.reset();
    formStatus.textContent = "Thanks! Your request has been received. We’ll review your details and contact you. Your booking is confirmed only after written agreement.";
    formStatus.setAttribute("role", "status");
  } catch (error) {
    formStatus.textContent = error instanceof Error ? error.message : "We couldn’t send your request. Please try again or call/text 570-494-7523.";
    formStatus.classList.add("form-status-error");
    formStatus.setAttribute("role", "alert");
  } finally {
    submitButton.disabled = false;
  }
});
