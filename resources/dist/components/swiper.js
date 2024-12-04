// node_modules/swiper/shared/ssr-window.esm.mjs
function isObject(obj) {
  return obj !== null && typeof obj === "object" && "constructor" in obj && obj.constructor === Object;
}
function extend(target, src) {
  if (target === void 0) {
    target = {};
  }
  if (src === void 0) {
    src = {};
  }
  Object.keys(src).forEach((key) => {
    if (typeof target[key] === "undefined")
      target[key] = src[key];
    else if (isObject(src[key]) && isObject(target[key]) && Object.keys(src[key]).length > 0) {
      extend(target[key], src[key]);
    }
  });
}
var ssrDocument = {
  body: {},
  addEventListener() {
  },
  removeEventListener() {
  },
  activeElement: {
    blur() {
    },
    nodeName: ""
  },
  querySelector() {
    return null;
  },
  querySelectorAll() {
    return [];
  },
  getElementById() {
    return null;
  },
  createEvent() {
    return {
      initEvent() {
      }
    };
  },
  createElement() {
    return {
      children: [],
      childNodes: [],
      style: {},
      setAttribute() {
      },
      getElementsByTagName() {
        return [];
      }
    };
  },
  createElementNS() {
    return {};
  },
  importNode() {
    return null;
  },
  location: {
    hash: "",
    host: "",
    hostname: "",
    href: "",
    origin: "",
    pathname: "",
    protocol: "",
    search: ""
  }
};
function getDocument() {
  const doc = typeof document !== "undefined" ? document : {};
  extend(doc, ssrDocument);
  return doc;
}
var ssrWindow = {
  document: ssrDocument,
  navigator: {
    userAgent: ""
  },
  location: {
    hash: "",
    host: "",
    hostname: "",
    href: "",
    origin: "",
    pathname: "",
    protocol: "",
    search: ""
  },
  history: {
    replaceState() {
    },
    pushState() {
    },
    go() {
    },
    back() {
    }
  },
  CustomEvent: function CustomEvent() {
    return this;
  },
  addEventListener() {
  },
  removeEventListener() {
  },
  getComputedStyle() {
    return {
      getPropertyValue() {
        return "";
      }
    };
  },
  Image() {
  },
  Date() {
  },
  screen: {},
  setTimeout() {
  },
  clearTimeout() {
  },
  matchMedia() {
    return {};
  },
  requestAnimationFrame(callback) {
    if (typeof setTimeout === "undefined") {
      callback();
      return null;
    }
    return setTimeout(callback, 0);
  },
  cancelAnimationFrame(id) {
    if (typeof setTimeout === "undefined") {
      return;
    }
    clearTimeout(id);
  }
};
function getWindow() {
  const win = typeof window !== "undefined" ? window : {};
  extend(win, ssrWindow);
  return win;
}

// node_modules/swiper/shared/utils.mjs
function classesToTokens(classes2) {
  if (classes2 === void 0) {
    classes2 = "";
  }
  return classes2.trim().split(" ").filter((c) => !!c.trim());
}
function deleteProps(obj) {
  const object = obj;
  Object.keys(object).forEach((key) => {
    try {
      object[key] = null;
    } catch (e) {
    }
    try {
      delete object[key];
    } catch (e) {
    }
  });
}
function nextTick(callback, delay) {
  if (delay === void 0) {
    delay = 0;
  }
  return setTimeout(callback, delay);
}
function now() {
  return Date.now();
}
function getComputedStyle2(el) {
  const window2 = getWindow();
  let style;
  if (window2.getComputedStyle) {
    style = window2.getComputedStyle(el, null);
  }
  if (!style && el.currentStyle) {
    style = el.currentStyle;
  }
  if (!style) {
    style = el.style;
  }
  return style;
}
function getTranslate(el, axis) {
  if (axis === void 0) {
    axis = "x";
  }
  const window2 = getWindow();
  let matrix;
  let curTransform;
  let transformMatrix;
  const curStyle = getComputedStyle2(el);
  if (window2.WebKitCSSMatrix) {
    curTransform = curStyle.transform || curStyle.webkitTransform;
    if (curTransform.split(",").length > 6) {
      curTransform = curTransform.split(", ").map((a) => a.replace(",", ".")).join(", ");
    }
    transformMatrix = new window2.WebKitCSSMatrix(curTransform === "none" ? "" : curTransform);
  } else {
    transformMatrix = curStyle.MozTransform || curStyle.OTransform || curStyle.MsTransform || curStyle.msTransform || curStyle.transform || curStyle.getPropertyValue("transform").replace("translate(", "matrix(1, 0, 0, 1,");
    matrix = transformMatrix.toString().split(",");
  }
  if (axis === "x") {
    if (window2.WebKitCSSMatrix)
      curTransform = transformMatrix.m41;
    else if (matrix.length === 16)
      curTransform = parseFloat(matrix[12]);
    else
      curTransform = parseFloat(matrix[4]);
  }
  if (axis === "y") {
    if (window2.WebKitCSSMatrix)
      curTransform = transformMatrix.m42;
    else if (matrix.length === 16)
      curTransform = parseFloat(matrix[13]);
    else
      curTransform = parseFloat(matrix[5]);
  }
  return curTransform || 0;
}
function isObject2(o) {
  return typeof o === "object" && o !== null && o.constructor && Object.prototype.toString.call(o).slice(8, -1) === "Object";
}
function isNode(node) {
  if (typeof window !== "undefined" && typeof window.HTMLElement !== "undefined") {
    return node instanceof HTMLElement;
  }
  return node && (node.nodeType === 1 || node.nodeType === 11);
}
function extend2() {
  const to = Object(arguments.length <= 0 ? void 0 : arguments[0]);
  const noExtend = ["__proto__", "constructor", "prototype"];
  for (let i = 1; i < arguments.length; i += 1) {
    const nextSource = i < 0 || arguments.length <= i ? void 0 : arguments[i];
    if (nextSource !== void 0 && nextSource !== null && !isNode(nextSource)) {
      const keysArray = Object.keys(Object(nextSource)).filter((key) => noExtend.indexOf(key) < 0);
      for (let nextIndex = 0, len = keysArray.length; nextIndex < len; nextIndex += 1) {
        const nextKey = keysArray[nextIndex];
        const desc = Object.getOwnPropertyDescriptor(nextSource, nextKey);
        if (desc !== void 0 && desc.enumerable) {
          if (isObject2(to[nextKey]) && isObject2(nextSource[nextKey])) {
            if (nextSource[nextKey].__swiper__) {
              to[nextKey] = nextSource[nextKey];
            } else {
              extend2(to[nextKey], nextSource[nextKey]);
            }
          } else if (!isObject2(to[nextKey]) && isObject2(nextSource[nextKey])) {
            to[nextKey] = {};
            if (nextSource[nextKey].__swiper__) {
              to[nextKey] = nextSource[nextKey];
            } else {
              extend2(to[nextKey], nextSource[nextKey]);
            }
          } else {
            to[nextKey] = nextSource[nextKey];
          }
        }
      }
    }
  }
  return to;
}
function setCSSProperty(el, varName, varValue) {
  el.style.setProperty(varName, varValue);
}
function animateCSSModeScroll(_ref) {
  let {
    swiper,
    targetPosition,
    side
  } = _ref;
  const window2 = getWindow();
  const startPosition = -swiper.translate;
  let startTime = null;
  let time;
  const duration = swiper.params.speed;
  swiper.wrapperEl.style.scrollSnapType = "none";
  window2.cancelAnimationFrame(swiper.cssModeFrameID);
  const dir = targetPosition > startPosition ? "next" : "prev";
  const isOutOfBound = (current, target) => {
    return dir === "next" && current >= target || dir === "prev" && current <= target;
  };
  const animate = () => {
    time = (/* @__PURE__ */ new Date()).getTime();
    if (startTime === null) {
      startTime = time;
    }
    const progress = Math.max(Math.min((time - startTime) / duration, 1), 0);
    const easeProgress = 0.5 - Math.cos(progress * Math.PI) / 2;
    let currentPosition = startPosition + easeProgress * (targetPosition - startPosition);
    if (isOutOfBound(currentPosition, targetPosition)) {
      currentPosition = targetPosition;
    }
    swiper.wrapperEl.scrollTo({
      [side]: currentPosition
    });
    if (isOutOfBound(currentPosition, targetPosition)) {
      swiper.wrapperEl.style.overflow = "hidden";
      swiper.wrapperEl.style.scrollSnapType = "";
      setTimeout(() => {
        swiper.wrapperEl.style.overflow = "";
        swiper.wrapperEl.scrollTo({
          [side]: currentPosition
        });
      });
      window2.cancelAnimationFrame(swiper.cssModeFrameID);
      return;
    }
    swiper.cssModeFrameID = window2.requestAnimationFrame(animate);
  };
  animate();
}
function elementChildren(element, selector) {
  if (selector === void 0) {
    selector = "";
  }
  const children = [...element.children];
  if (element instanceof HTMLSlotElement) {
    children.push(...element.assignedElements());
  }
  if (!selector) {
    return children;
  }
  return children.filter((el) => el.matches(selector));
}
function elementIsChildOf(el, parent) {
  const isChild = parent.contains(el);
  if (!isChild && parent instanceof HTMLSlotElement) {
    const children = [...parent.assignedElements()];
    return children.includes(el);
  }
  return isChild;
}
function showWarning(text) {
  try {
    console.warn(text);
    return;
  } catch (err) {
  }
}
function createElement(tag, classes2) {
  if (classes2 === void 0) {
    classes2 = [];
  }
  const el = document.createElement(tag);
  el.classList.add(...Array.isArray(classes2) ? classes2 : classesToTokens(classes2));
  return el;
}
function elementPrevAll(el, selector) {
  const prevEls = [];
  while (el.previousElementSibling) {
    const prev = el.previousElementSibling;
    if (selector) {
      if (prev.matches(selector))
        prevEls.push(prev);
    } else
      prevEls.push(prev);
    el = prev;
  }
  return prevEls;
}
function elementNextAll(el, selector) {
  const nextEls = [];
  while (el.nextElementSibling) {
    const next = el.nextElementSibling;
    if (selector) {
      if (next.matches(selector))
        nextEls.push(next);
    } else
      nextEls.push(next);
    el = next;
  }
  return nextEls;
}
function elementStyle(el, prop) {
  const window2 = getWindow();
  return window2.getComputedStyle(el, null).getPropertyValue(prop);
}
function elementIndex(el) {
  let child = el;
  let i;
  if (child) {
    i = 0;
    while ((child = child.previousSibling) !== null) {
      if (child.nodeType === 1)
        i += 1;
    }
    return i;
  }
  return void 0;
}
function elementParents(el, selector) {
  const parents = [];
  let parent = el.parentElement;
  while (parent) {
    if (selector) {
      if (parent.matches(selector))
        parents.push(parent);
    } else {
      parents.push(parent);
    }
    parent = parent.parentElement;
  }
  return parents;
}
function elementTransitionEnd(el, callback) {
  function fireCallBack(e) {
    if (e.target !== el)
      return;
    callback.call(el, e);
    el.removeEventListener("transitionend", fireCallBack);
  }
  if (callback) {
    el.addEventListener("transitionend", fireCallBack);
  }
}
function elementOuterSize(el, size, includeMargins) {
  const window2 = getWindow();
  if (includeMargins) {
    return el[size === "width" ? "offsetWidth" : "offsetHeight"] + parseFloat(window2.getComputedStyle(el, null).getPropertyValue(size === "width" ? "margin-right" : "margin-top")) + parseFloat(window2.getComputedStyle(el, null).getPropertyValue(size === "width" ? "margin-left" : "margin-bottom"));
  }
  return el.offsetWidth;
}
function makeElementsArray(el) {
  return (Array.isArray(el) ? el : [el]).filter((e) => !!e);
}

// node_modules/swiper/shared/swiper-core.mjs
var support;
function calcSupport() {
  const window2 = getWindow();
  const document2 = getDocument();
  return {
    smoothScroll: document2.documentElement && document2.documentElement.style && "scrollBehavior" in document2.documentElement.style,
    touch: !!("ontouchstart" in window2 || window2.DocumentTouch && document2 instanceof window2.DocumentTouch)
  };
}
function getSupport() {
  if (!support) {
    support = calcSupport();
  }
  return support;
}
var deviceCached;
function calcDevice(_temp) {
  let {
    userAgent
  } = _temp === void 0 ? {} : _temp;
  const support2 = getSupport();
  const window2 = getWindow();
  const platform = window2.navigator.platform;
  const ua = userAgent || window2.navigator.userAgent;
  const device = {
    ios: false,
    android: false
  };
  const screenWidth = window2.screen.width;
  const screenHeight = window2.screen.height;
  const android = ua.match(/(Android);?[\s\/]+([\d.]+)?/);
  let ipad = ua.match(/(iPad).*OS\s([\d_]+)/);
  const ipod = ua.match(/(iPod)(.*OS\s([\d_]+))?/);
  const iphone = !ipad && ua.match(/(iPhone\sOS|iOS)\s([\d_]+)/);
  const windows = platform === "Win32";
  let macos = platform === "MacIntel";
  const iPadScreens = ["1024x1366", "1366x1024", "834x1194", "1194x834", "834x1112", "1112x834", "768x1024", "1024x768", "820x1180", "1180x820", "810x1080", "1080x810"];
  if (!ipad && macos && support2.touch && iPadScreens.indexOf(`${screenWidth}x${screenHeight}`) >= 0) {
    ipad = ua.match(/(Version)\/([\d.]+)/);
    if (!ipad)
      ipad = [0, 1, "13_0_0"];
    macos = false;
  }
  if (android && !windows) {
    device.os = "android";
    device.android = true;
  }
  if (ipad || iphone || ipod) {
    device.os = "ios";
    device.ios = true;
  }
  return device;
}
function getDevice(overrides) {
  if (overrides === void 0) {
    overrides = {};
  }
  if (!deviceCached) {
    deviceCached = calcDevice(overrides);
  }
  return deviceCached;
}
var browser;
function calcBrowser() {
  const window2 = getWindow();
  const device = getDevice();
  let needPerspectiveFix = false;
  function isSafari() {
    const ua = window2.navigator.userAgent.toLowerCase();
    return ua.indexOf("safari") >= 0 && ua.indexOf("chrome") < 0 && ua.indexOf("android") < 0;
  }
  if (isSafari()) {
    const ua = String(window2.navigator.userAgent);
    if (ua.includes("Version/")) {
      const [major, minor] = ua.split("Version/")[1].split(" ")[0].split(".").map((num) => Number(num));
      needPerspectiveFix = major < 16 || major === 16 && minor < 2;
    }
  }
  const isWebView = /(iPhone|iPod|iPad).*AppleWebKit(?!.*Safari)/i.test(window2.navigator.userAgent);
  const isSafariBrowser = isSafari();
  const need3dFix = isSafariBrowser || isWebView && device.ios;
  return {
    isSafari: needPerspectiveFix || isSafariBrowser,
    needPerspectiveFix,
    need3dFix,
    isWebView
  };
}
function getBrowser() {
  if (!browser) {
    browser = calcBrowser();
  }
  return browser;
}
function Resize(_ref) {
  let {
    swiper,
    on,
    emit
  } = _ref;
  const window2 = getWindow();
  let observer = null;
  let animationFrame = null;
  const resizeHandler = () => {
    if (!swiper || swiper.destroyed || !swiper.initialized)
      return;
    emit("beforeResize");
    emit("resize");
  };
  const createObserver = () => {
    if (!swiper || swiper.destroyed || !swiper.initialized)
      return;
    observer = new ResizeObserver((entries) => {
      animationFrame = window2.requestAnimationFrame(() => {
        const {
          width,
          height
        } = swiper;
        let newWidth = width;
        let newHeight = height;
        entries.forEach((_ref2) => {
          let {
            contentBoxSize,
            contentRect,
            target
          } = _ref2;
          if (target && target !== swiper.el)
            return;
          newWidth = contentRect ? contentRect.width : (contentBoxSize[0] || contentBoxSize).inlineSize;
          newHeight = contentRect ? contentRect.height : (contentBoxSize[0] || contentBoxSize).blockSize;
        });
        if (newWidth !== width || newHeight !== height) {
          resizeHandler();
        }
      });
    });
    observer.observe(swiper.el);
  };
  const removeObserver = () => {
    if (animationFrame) {
      window2.cancelAnimationFrame(animationFrame);
    }
    if (observer && observer.unobserve && swiper.el) {
      observer.unobserve(swiper.el);
      observer = null;
    }
  };
  const orientationChangeHandler = () => {
    if (!swiper || swiper.destroyed || !swiper.initialized)
      return;
    emit("orientationchange");
  };
  on("init", () => {
    if (swiper.params.resizeObserver && typeof window2.ResizeObserver !== "undefined") {
      createObserver();
      return;
    }
    window2.addEventListener("resize", resizeHandler);
    window2.addEventListener("orientationchange", orientationChangeHandler);
  });
  on("destroy", () => {
    removeObserver();
    window2.removeEventListener("resize", resizeHandler);
    window2.removeEventListener("orientationchange", orientationChangeHandler);
  });
}
function Observer(_ref) {
  let {
    swiper,
    extendParams,
    on,
    emit
  } = _ref;
  const observers = [];
  const window2 = getWindow();
  const attach = function(target, options) {
    if (options === void 0) {
      options = {};
    }
    const ObserverFunc = window2.MutationObserver || window2.WebkitMutationObserver;
    const observer = new ObserverFunc((mutations) => {
      if (swiper.__preventObserver__)
        return;
      if (mutations.length === 1) {
        emit("observerUpdate", mutations[0]);
        return;
      }
      const observerUpdate = function observerUpdate2() {
        emit("observerUpdate", mutations[0]);
      };
      if (window2.requestAnimationFrame) {
        window2.requestAnimationFrame(observerUpdate);
      } else {
        window2.setTimeout(observerUpdate, 0);
      }
    });
    observer.observe(target, {
      attributes: typeof options.attributes === "undefined" ? true : options.attributes,
      childList: swiper.isElement || (typeof options.childList === "undefined" ? true : options).childList,
      characterData: typeof options.characterData === "undefined" ? true : options.characterData
    });
    observers.push(observer);
  };
  const init = () => {
    if (!swiper.params.observer)
      return;
    if (swiper.params.observeParents) {
      const containerParents = elementParents(swiper.hostEl);
      for (let i = 0; i < containerParents.length; i += 1) {
        attach(containerParents[i]);
      }
    }
    attach(swiper.hostEl, {
      childList: swiper.params.observeSlideChildren
    });
    attach(swiper.wrapperEl, {
      attributes: false
    });
  };
  const destroy = () => {
    observers.forEach((observer) => {
      observer.disconnect();
    });
    observers.splice(0, observers.length);
  };
  extendParams({
    observer: false,
    observeParents: false,
    observeSlideChildren: false
  });
  on("init", init);
  on("destroy", destroy);
}
var eventsEmitter = {
  on(events2, handler, priority) {
    const self = this;
    if (!self.eventsListeners || self.destroyed)
      return self;
    if (typeof handler !== "function")
      return self;
    const method = priority ? "unshift" : "push";
    events2.split(" ").forEach((event2) => {
      if (!self.eventsListeners[event2])
        self.eventsListeners[event2] = [];
      self.eventsListeners[event2][method](handler);
    });
    return self;
  },
  once(events2, handler, priority) {
    const self = this;
    if (!self.eventsListeners || self.destroyed)
      return self;
    if (typeof handler !== "function")
      return self;
    function onceHandler() {
      self.off(events2, onceHandler);
      if (onceHandler.__emitterProxy) {
        delete onceHandler.__emitterProxy;
      }
      for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
        args[_key] = arguments[_key];
      }
      handler.apply(self, args);
    }
    onceHandler.__emitterProxy = handler;
    return self.on(events2, onceHandler, priority);
  },
  onAny(handler, priority) {
    const self = this;
    if (!self.eventsListeners || self.destroyed)
      return self;
    if (typeof handler !== "function")
      return self;
    const method = priority ? "unshift" : "push";
    if (self.eventsAnyListeners.indexOf(handler) < 0) {
      self.eventsAnyListeners[method](handler);
    }
    return self;
  },
  offAny(handler) {
    const self = this;
    if (!self.eventsListeners || self.destroyed)
      return self;
    if (!self.eventsAnyListeners)
      return self;
    const index = self.eventsAnyListeners.indexOf(handler);
    if (index >= 0) {
      self.eventsAnyListeners.splice(index, 1);
    }
    return self;
  },
  off(events2, handler) {
    const self = this;
    if (!self.eventsListeners || self.destroyed)
      return self;
    if (!self.eventsListeners)
      return self;
    events2.split(" ").forEach((event2) => {
      if (typeof handler === "undefined") {
        self.eventsListeners[event2] = [];
      } else if (self.eventsListeners[event2]) {
        self.eventsListeners[event2].forEach((eventHandler, index) => {
          if (eventHandler === handler || eventHandler.__emitterProxy && eventHandler.__emitterProxy === handler) {
            self.eventsListeners[event2].splice(index, 1);
          }
        });
      }
    });
    return self;
  },
  emit() {
    const self = this;
    if (!self.eventsListeners || self.destroyed)
      return self;
    if (!self.eventsListeners)
      return self;
    let events2;
    let data;
    let context;
    for (var _len2 = arguments.length, args = new Array(_len2), _key2 = 0; _key2 < _len2; _key2++) {
      args[_key2] = arguments[_key2];
    }
    if (typeof args[0] === "string" || Array.isArray(args[0])) {
      events2 = args[0];
      data = args.slice(1, args.length);
      context = self;
    } else {
      events2 = args[0].events;
      data = args[0].data;
      context = args[0].context || self;
    }
    data.unshift(context);
    const eventsArray = Array.isArray(events2) ? events2 : events2.split(" ");
    eventsArray.forEach((event2) => {
      if (self.eventsAnyListeners && self.eventsAnyListeners.length) {
        self.eventsAnyListeners.forEach((eventHandler) => {
          eventHandler.apply(context, [event2, ...data]);
        });
      }
      if (self.eventsListeners && self.eventsListeners[event2]) {
        self.eventsListeners[event2].forEach((eventHandler) => {
          eventHandler.apply(context, data);
        });
      }
    });
    return self;
  }
};
function updateSize() {
  const swiper = this;
  let width;
  let height;
  const el = swiper.el;
  if (typeof swiper.params.width !== "undefined" && swiper.params.width !== null) {
    width = swiper.params.width;
  } else {
    width = el.clientWidth;
  }
  if (typeof swiper.params.height !== "undefined" && swiper.params.height !== null) {
    height = swiper.params.height;
  } else {
    height = el.clientHeight;
  }
  if (width === 0 && swiper.isHorizontal() || height === 0 && swiper.isVertical()) {
    return;
  }
  width = width - parseInt(elementStyle(el, "padding-left") || 0, 10) - parseInt(elementStyle(el, "padding-right") || 0, 10);
  height = height - parseInt(elementStyle(el, "padding-top") || 0, 10) - parseInt(elementStyle(el, "padding-bottom") || 0, 10);
  if (Number.isNaN(width))
    width = 0;
  if (Number.isNaN(height))
    height = 0;
  Object.assign(swiper, {
    width,
    height,
    size: swiper.isHorizontal() ? width : height
  });
}
function updateSlides() {
  const swiper = this;
  function getDirectionPropertyValue(node, label) {
    return parseFloat(node.getPropertyValue(swiper.getDirectionLabel(label)) || 0);
  }
  const params = swiper.params;
  const {
    wrapperEl,
    slidesEl,
    size: swiperSize,
    rtlTranslate: rtl,
    wrongRTL
  } = swiper;
  const isVirtual = swiper.virtual && params.virtual.enabled;
  const previousSlidesLength = isVirtual ? swiper.virtual.slides.length : swiper.slides.length;
  const slides = elementChildren(slidesEl, `.${swiper.params.slideClass}, swiper-slide`);
  const slidesLength = isVirtual ? swiper.virtual.slides.length : slides.length;
  let snapGrid = [];
  const slidesGrid = [];
  const slidesSizesGrid = [];
  let offsetBefore = params.slidesOffsetBefore;
  if (typeof offsetBefore === "function") {
    offsetBefore = params.slidesOffsetBefore.call(swiper);
  }
  let offsetAfter = params.slidesOffsetAfter;
  if (typeof offsetAfter === "function") {
    offsetAfter = params.slidesOffsetAfter.call(swiper);
  }
  const previousSnapGridLength = swiper.snapGrid.length;
  const previousSlidesGridLength = swiper.slidesGrid.length;
  let spaceBetween = params.spaceBetween;
  let slidePosition = -offsetBefore;
  let prevSlideSize = 0;
  let index = 0;
  if (typeof swiperSize === "undefined") {
    return;
  }
  if (typeof spaceBetween === "string" && spaceBetween.indexOf("%") >= 0) {
    spaceBetween = parseFloat(spaceBetween.replace("%", "")) / 100 * swiperSize;
  } else if (typeof spaceBetween === "string") {
    spaceBetween = parseFloat(spaceBetween);
  }
  swiper.virtualSize = -spaceBetween;
  slides.forEach((slideEl) => {
    if (rtl) {
      slideEl.style.marginLeft = "";
    } else {
      slideEl.style.marginRight = "";
    }
    slideEl.style.marginBottom = "";
    slideEl.style.marginTop = "";
  });
  if (params.centeredSlides && params.cssMode) {
    setCSSProperty(wrapperEl, "--swiper-centered-offset-before", "");
    setCSSProperty(wrapperEl, "--swiper-centered-offset-after", "");
  }
  const gridEnabled = params.grid && params.grid.rows > 1 && swiper.grid;
  if (gridEnabled) {
    swiper.grid.initSlides(slides);
  } else if (swiper.grid) {
    swiper.grid.unsetSlides();
  }
  let slideSize;
  const shouldResetSlideSize = params.slidesPerView === "auto" && params.breakpoints && Object.keys(params.breakpoints).filter((key) => {
    return typeof params.breakpoints[key].slidesPerView !== "undefined";
  }).length > 0;
  for (let i = 0; i < slidesLength; i += 1) {
    slideSize = 0;
    let slide2;
    if (slides[i])
      slide2 = slides[i];
    if (gridEnabled) {
      swiper.grid.updateSlide(i, slide2, slides);
    }
    if (slides[i] && elementStyle(slide2, "display") === "none")
      continue;
    if (params.slidesPerView === "auto") {
      if (shouldResetSlideSize) {
        slides[i].style[swiper.getDirectionLabel("width")] = ``;
      }
      const slideStyles = getComputedStyle(slide2);
      const currentTransform = slide2.style.transform;
      const currentWebKitTransform = slide2.style.webkitTransform;
      if (currentTransform) {
        slide2.style.transform = "none";
      }
      if (currentWebKitTransform) {
        slide2.style.webkitTransform = "none";
      }
      if (params.roundLengths) {
        slideSize = swiper.isHorizontal() ? elementOuterSize(slide2, "width", true) : elementOuterSize(slide2, "height", true);
      } else {
        const width = getDirectionPropertyValue(slideStyles, "width");
        const paddingLeft = getDirectionPropertyValue(slideStyles, "padding-left");
        const paddingRight = getDirectionPropertyValue(slideStyles, "padding-right");
        const marginLeft = getDirectionPropertyValue(slideStyles, "margin-left");
        const marginRight = getDirectionPropertyValue(slideStyles, "margin-right");
        const boxSizing = slideStyles.getPropertyValue("box-sizing");
        if (boxSizing && boxSizing === "border-box") {
          slideSize = width + marginLeft + marginRight;
        } else {
          const {
            clientWidth,
            offsetWidth
          } = slide2;
          slideSize = width + paddingLeft + paddingRight + marginLeft + marginRight + (offsetWidth - clientWidth);
        }
      }
      if (currentTransform) {
        slide2.style.transform = currentTransform;
      }
      if (currentWebKitTransform) {
        slide2.style.webkitTransform = currentWebKitTransform;
      }
      if (params.roundLengths)
        slideSize = Math.floor(slideSize);
    } else {
      slideSize = (swiperSize - (params.slidesPerView - 1) * spaceBetween) / params.slidesPerView;
      if (params.roundLengths)
        slideSize = Math.floor(slideSize);
      if (slides[i]) {
        slides[i].style[swiper.getDirectionLabel("width")] = `${slideSize}px`;
      }
    }
    if (slides[i]) {
      slides[i].swiperSlideSize = slideSize;
    }
    slidesSizesGrid.push(slideSize);
    if (params.centeredSlides) {
      slidePosition = slidePosition + slideSize / 2 + prevSlideSize / 2 + spaceBetween;
      if (prevSlideSize === 0 && i !== 0)
        slidePosition = slidePosition - swiperSize / 2 - spaceBetween;
      if (i === 0)
        slidePosition = slidePosition - swiperSize / 2 - spaceBetween;
      if (Math.abs(slidePosition) < 1 / 1e3)
        slidePosition = 0;
      if (params.roundLengths)
        slidePosition = Math.floor(slidePosition);
      if (index % params.slidesPerGroup === 0)
        snapGrid.push(slidePosition);
      slidesGrid.push(slidePosition);
    } else {
      if (params.roundLengths)
        slidePosition = Math.floor(slidePosition);
      if ((index - Math.min(swiper.params.slidesPerGroupSkip, index)) % swiper.params.slidesPerGroup === 0)
        snapGrid.push(slidePosition);
      slidesGrid.push(slidePosition);
      slidePosition = slidePosition + slideSize + spaceBetween;
    }
    swiper.virtualSize += slideSize + spaceBetween;
    prevSlideSize = slideSize;
    index += 1;
  }
  swiper.virtualSize = Math.max(swiper.virtualSize, swiperSize) + offsetAfter;
  if (rtl && wrongRTL && (params.effect === "slide" || params.effect === "coverflow")) {
    wrapperEl.style.width = `${swiper.virtualSize + spaceBetween}px`;
  }
  if (params.setWrapperSize) {
    wrapperEl.style[swiper.getDirectionLabel("width")] = `${swiper.virtualSize + spaceBetween}px`;
  }
  if (gridEnabled) {
    swiper.grid.updateWrapperSize(slideSize, snapGrid);
  }
  if (!params.centeredSlides) {
    const newSlidesGrid = [];
    for (let i = 0; i < snapGrid.length; i += 1) {
      let slidesGridItem = snapGrid[i];
      if (params.roundLengths)
        slidesGridItem = Math.floor(slidesGridItem);
      if (snapGrid[i] <= swiper.virtualSize - swiperSize) {
        newSlidesGrid.push(slidesGridItem);
      }
    }
    snapGrid = newSlidesGrid;
    if (Math.floor(swiper.virtualSize - swiperSize) - Math.floor(snapGrid[snapGrid.length - 1]) > 1) {
      snapGrid.push(swiper.virtualSize - swiperSize);
    }
  }
  if (isVirtual && params.loop) {
    const size = slidesSizesGrid[0] + spaceBetween;
    if (params.slidesPerGroup > 1) {
      const groups = Math.ceil((swiper.virtual.slidesBefore + swiper.virtual.slidesAfter) / params.slidesPerGroup);
      const groupSize = size * params.slidesPerGroup;
      for (let i = 0; i < groups; i += 1) {
        snapGrid.push(snapGrid[snapGrid.length - 1] + groupSize);
      }
    }
    for (let i = 0; i < swiper.virtual.slidesBefore + swiper.virtual.slidesAfter; i += 1) {
      if (params.slidesPerGroup === 1) {
        snapGrid.push(snapGrid[snapGrid.length - 1] + size);
      }
      slidesGrid.push(slidesGrid[slidesGrid.length - 1] + size);
      swiper.virtualSize += size;
    }
  }
  if (snapGrid.length === 0)
    snapGrid = [0];
  if (spaceBetween !== 0) {
    const key = swiper.isHorizontal() && rtl ? "marginLeft" : swiper.getDirectionLabel("marginRight");
    slides.filter((_, slideIndex) => {
      if (!params.cssMode || params.loop)
        return true;
      if (slideIndex === slides.length - 1) {
        return false;
      }
      return true;
    }).forEach((slideEl) => {
      slideEl.style[key] = `${spaceBetween}px`;
    });
  }
  if (params.centeredSlides && params.centeredSlidesBounds) {
    let allSlidesSize = 0;
    slidesSizesGrid.forEach((slideSizeValue) => {
      allSlidesSize += slideSizeValue + (spaceBetween || 0);
    });
    allSlidesSize -= spaceBetween;
    const maxSnap = allSlidesSize > swiperSize ? allSlidesSize - swiperSize : 0;
    snapGrid = snapGrid.map((snap) => {
      if (snap <= 0)
        return -offsetBefore;
      if (snap > maxSnap)
        return maxSnap + offsetAfter;
      return snap;
    });
  }
  if (params.centerInsufficientSlides) {
    let allSlidesSize = 0;
    slidesSizesGrid.forEach((slideSizeValue) => {
      allSlidesSize += slideSizeValue + (spaceBetween || 0);
    });
    allSlidesSize -= spaceBetween;
    const offsetSize = (params.slidesOffsetBefore || 0) + (params.slidesOffsetAfter || 0);
    if (allSlidesSize + offsetSize < swiperSize) {
      const allSlidesOffset = (swiperSize - allSlidesSize - offsetSize) / 2;
      snapGrid.forEach((snap, snapIndex) => {
        snapGrid[snapIndex] = snap - allSlidesOffset;
      });
      slidesGrid.forEach((snap, snapIndex) => {
        slidesGrid[snapIndex] = snap + allSlidesOffset;
      });
    }
  }
  Object.assign(swiper, {
    slides,
    snapGrid,
    slidesGrid,
    slidesSizesGrid
  });
  if (params.centeredSlides && params.cssMode && !params.centeredSlidesBounds) {
    setCSSProperty(wrapperEl, "--swiper-centered-offset-before", `${-snapGrid[0]}px`);
    setCSSProperty(wrapperEl, "--swiper-centered-offset-after", `${swiper.size / 2 - slidesSizesGrid[slidesSizesGrid.length - 1] / 2}px`);
    const addToSnapGrid = -swiper.snapGrid[0];
    const addToSlidesGrid = -swiper.slidesGrid[0];
    swiper.snapGrid = swiper.snapGrid.map((v) => v + addToSnapGrid);
    swiper.slidesGrid = swiper.slidesGrid.map((v) => v + addToSlidesGrid);
  }
  if (slidesLength !== previousSlidesLength) {
    swiper.emit("slidesLengthChange");
  }
  if (snapGrid.length !== previousSnapGridLength) {
    if (swiper.params.watchOverflow)
      swiper.checkOverflow();
    swiper.emit("snapGridLengthChange");
  }
  if (slidesGrid.length !== previousSlidesGridLength) {
    swiper.emit("slidesGridLengthChange");
  }
  if (params.watchSlidesProgress) {
    swiper.updateSlidesOffset();
  }
  swiper.emit("slidesUpdated");
  if (!isVirtual && !params.cssMode && (params.effect === "slide" || params.effect === "fade")) {
    const backFaceHiddenClass = `${params.containerModifierClass}backface-hidden`;
    const hasClassBackfaceClassAdded = swiper.el.classList.contains(backFaceHiddenClass);
    if (slidesLength <= params.maxBackfaceHiddenSlides) {
      if (!hasClassBackfaceClassAdded)
        swiper.el.classList.add(backFaceHiddenClass);
    } else if (hasClassBackfaceClassAdded) {
      swiper.el.classList.remove(backFaceHiddenClass);
    }
  }
}
function updateAutoHeight(speed) {
  const swiper = this;
  const activeSlides = [];
  const isVirtual = swiper.virtual && swiper.params.virtual.enabled;
  let newHeight = 0;
  let i;
  if (typeof speed === "number") {
    swiper.setTransition(speed);
  } else if (speed === true) {
    swiper.setTransition(swiper.params.speed);
  }
  const getSlideByIndex = (index) => {
    if (isVirtual) {
      return swiper.slides[swiper.getSlideIndexByData(index)];
    }
    return swiper.slides[index];
  };
  if (swiper.params.slidesPerView !== "auto" && swiper.params.slidesPerView > 1) {
    if (swiper.params.centeredSlides) {
      (swiper.visibleSlides || []).forEach((slide2) => {
        activeSlides.push(slide2);
      });
    } else {
      for (i = 0; i < Math.ceil(swiper.params.slidesPerView); i += 1) {
        const index = swiper.activeIndex + i;
        if (index > swiper.slides.length && !isVirtual)
          break;
        activeSlides.push(getSlideByIndex(index));
      }
    }
  } else {
    activeSlides.push(getSlideByIndex(swiper.activeIndex));
  }
  for (i = 0; i < activeSlides.length; i += 1) {
    if (typeof activeSlides[i] !== "undefined") {
      const height = activeSlides[i].offsetHeight;
      newHeight = height > newHeight ? height : newHeight;
    }
  }
  if (newHeight || newHeight === 0)
    swiper.wrapperEl.style.height = `${newHeight}px`;
}
function updateSlidesOffset() {
  const swiper = this;
  const slides = swiper.slides;
  const minusOffset = swiper.isElement ? swiper.isHorizontal() ? swiper.wrapperEl.offsetLeft : swiper.wrapperEl.offsetTop : 0;
  for (let i = 0; i < slides.length; i += 1) {
    slides[i].swiperSlideOffset = (swiper.isHorizontal() ? slides[i].offsetLeft : slides[i].offsetTop) - minusOffset - swiper.cssOverflowAdjustment();
  }
}
var toggleSlideClasses$1 = (slideEl, condition, className) => {
  if (condition && !slideEl.classList.contains(className)) {
    slideEl.classList.add(className);
  } else if (!condition && slideEl.classList.contains(className)) {
    slideEl.classList.remove(className);
  }
};
function updateSlidesProgress(translate2) {
  if (translate2 === void 0) {
    translate2 = this && this.translate || 0;
  }
  const swiper = this;
  const params = swiper.params;
  const {
    slides,
    rtlTranslate: rtl,
    snapGrid
  } = swiper;
  if (slides.length === 0)
    return;
  if (typeof slides[0].swiperSlideOffset === "undefined")
    swiper.updateSlidesOffset();
  let offsetCenter = -translate2;
  if (rtl)
    offsetCenter = translate2;
  swiper.visibleSlidesIndexes = [];
  swiper.visibleSlides = [];
  let spaceBetween = params.spaceBetween;
  if (typeof spaceBetween === "string" && spaceBetween.indexOf("%") >= 0) {
    spaceBetween = parseFloat(spaceBetween.replace("%", "")) / 100 * swiper.size;
  } else if (typeof spaceBetween === "string") {
    spaceBetween = parseFloat(spaceBetween);
  }
  for (let i = 0; i < slides.length; i += 1) {
    const slide2 = slides[i];
    let slideOffset = slide2.swiperSlideOffset;
    if (params.cssMode && params.centeredSlides) {
      slideOffset -= slides[0].swiperSlideOffset;
    }
    const slideProgress = (offsetCenter + (params.centeredSlides ? swiper.minTranslate() : 0) - slideOffset) / (slide2.swiperSlideSize + spaceBetween);
    const originalSlideProgress = (offsetCenter - snapGrid[0] + (params.centeredSlides ? swiper.minTranslate() : 0) - slideOffset) / (slide2.swiperSlideSize + spaceBetween);
    const slideBefore = -(offsetCenter - slideOffset);
    const slideAfter = slideBefore + swiper.slidesSizesGrid[i];
    const isFullyVisible = slideBefore >= 0 && slideBefore <= swiper.size - swiper.slidesSizesGrid[i];
    const isVisible = slideBefore >= 0 && slideBefore < swiper.size - 1 || slideAfter > 1 && slideAfter <= swiper.size || slideBefore <= 0 && slideAfter >= swiper.size;
    if (isVisible) {
      swiper.visibleSlides.push(slide2);
      swiper.visibleSlidesIndexes.push(i);
    }
    toggleSlideClasses$1(slide2, isVisible, params.slideVisibleClass);
    toggleSlideClasses$1(slide2, isFullyVisible, params.slideFullyVisibleClass);
    slide2.progress = rtl ? -slideProgress : slideProgress;
    slide2.originalProgress = rtl ? -originalSlideProgress : originalSlideProgress;
  }
}
function updateProgress(translate2) {
  const swiper = this;
  if (typeof translate2 === "undefined") {
    const multiplier = swiper.rtlTranslate ? -1 : 1;
    translate2 = swiper && swiper.translate && swiper.translate * multiplier || 0;
  }
  const params = swiper.params;
  const translatesDiff = swiper.maxTranslate() - swiper.minTranslate();
  let {
    progress,
    isBeginning,
    isEnd,
    progressLoop
  } = swiper;
  const wasBeginning = isBeginning;
  const wasEnd = isEnd;
  if (translatesDiff === 0) {
    progress = 0;
    isBeginning = true;
    isEnd = true;
  } else {
    progress = (translate2 - swiper.minTranslate()) / translatesDiff;
    const isBeginningRounded = Math.abs(translate2 - swiper.minTranslate()) < 1;
    const isEndRounded = Math.abs(translate2 - swiper.maxTranslate()) < 1;
    isBeginning = isBeginningRounded || progress <= 0;
    isEnd = isEndRounded || progress >= 1;
    if (isBeginningRounded)
      progress = 0;
    if (isEndRounded)
      progress = 1;
  }
  if (params.loop) {
    const firstSlideIndex = swiper.getSlideIndexByData(0);
    const lastSlideIndex = swiper.getSlideIndexByData(swiper.slides.length - 1);
    const firstSlideTranslate = swiper.slidesGrid[firstSlideIndex];
    const lastSlideTranslate = swiper.slidesGrid[lastSlideIndex];
    const translateMax = swiper.slidesGrid[swiper.slidesGrid.length - 1];
    const translateAbs = Math.abs(translate2);
    if (translateAbs >= firstSlideTranslate) {
      progressLoop = (translateAbs - firstSlideTranslate) / translateMax;
    } else {
      progressLoop = (translateAbs + translateMax - lastSlideTranslate) / translateMax;
    }
    if (progressLoop > 1)
      progressLoop -= 1;
  }
  Object.assign(swiper, {
    progress,
    progressLoop,
    isBeginning,
    isEnd
  });
  if (params.watchSlidesProgress || params.centeredSlides && params.autoHeight)
    swiper.updateSlidesProgress(translate2);
  if (isBeginning && !wasBeginning) {
    swiper.emit("reachBeginning toEdge");
  }
  if (isEnd && !wasEnd) {
    swiper.emit("reachEnd toEdge");
  }
  if (wasBeginning && !isBeginning || wasEnd && !isEnd) {
    swiper.emit("fromEdge");
  }
  swiper.emit("progress", progress);
}
var toggleSlideClasses = (slideEl, condition, className) => {
  if (condition && !slideEl.classList.contains(className)) {
    slideEl.classList.add(className);
  } else if (!condition && slideEl.classList.contains(className)) {
    slideEl.classList.remove(className);
  }
};
function updateSlidesClasses() {
  const swiper = this;
  const {
    slides,
    params,
    slidesEl,
    activeIndex
  } = swiper;
  const isVirtual = swiper.virtual && params.virtual.enabled;
  const gridEnabled = swiper.grid && params.grid && params.grid.rows > 1;
  const getFilteredSlide = (selector) => {
    return elementChildren(slidesEl, `.${params.slideClass}${selector}, swiper-slide${selector}`)[0];
  };
  let activeSlide;
  let prevSlide;
  let nextSlide;
  if (isVirtual) {
    if (params.loop) {
      let slideIndex = activeIndex - swiper.virtual.slidesBefore;
      if (slideIndex < 0)
        slideIndex = swiper.virtual.slides.length + slideIndex;
      if (slideIndex >= swiper.virtual.slides.length)
        slideIndex -= swiper.virtual.slides.length;
      activeSlide = getFilteredSlide(`[data-swiper-slide-index="${slideIndex}"]`);
    } else {
      activeSlide = getFilteredSlide(`[data-swiper-slide-index="${activeIndex}"]`);
    }
  } else {
    if (gridEnabled) {
      activeSlide = slides.filter((slideEl) => slideEl.column === activeIndex)[0];
      nextSlide = slides.filter((slideEl) => slideEl.column === activeIndex + 1)[0];
      prevSlide = slides.filter((slideEl) => slideEl.column === activeIndex - 1)[0];
    } else {
      activeSlide = slides[activeIndex];
    }
  }
  if (activeSlide) {
    if (!gridEnabled) {
      nextSlide = elementNextAll(activeSlide, `.${params.slideClass}, swiper-slide`)[0];
      if (params.loop && !nextSlide) {
        nextSlide = slides[0];
      }
      prevSlide = elementPrevAll(activeSlide, `.${params.slideClass}, swiper-slide`)[0];
      if (params.loop && !prevSlide === 0) {
        prevSlide = slides[slides.length - 1];
      }
    }
  }
  slides.forEach((slideEl) => {
    toggleSlideClasses(slideEl, slideEl === activeSlide, params.slideActiveClass);
    toggleSlideClasses(slideEl, slideEl === nextSlide, params.slideNextClass);
    toggleSlideClasses(slideEl, slideEl === prevSlide, params.slidePrevClass);
  });
  swiper.emitSlidesClasses();
}
var processLazyPreloader = (swiper, imageEl) => {
  if (!swiper || swiper.destroyed || !swiper.params)
    return;
  const slideSelector = () => swiper.isElement ? `swiper-slide` : `.${swiper.params.slideClass}`;
  const slideEl = imageEl.closest(slideSelector());
  if (slideEl) {
    let lazyEl = slideEl.querySelector(`.${swiper.params.lazyPreloaderClass}`);
    if (!lazyEl && swiper.isElement) {
      if (slideEl.shadowRoot) {
        lazyEl = slideEl.shadowRoot.querySelector(`.${swiper.params.lazyPreloaderClass}`);
      } else {
        requestAnimationFrame(() => {
          if (slideEl.shadowRoot) {
            lazyEl = slideEl.shadowRoot.querySelector(`.${swiper.params.lazyPreloaderClass}`);
            if (lazyEl)
              lazyEl.remove();
          }
        });
      }
    }
    if (lazyEl)
      lazyEl.remove();
  }
};
var unlazy = (swiper, index) => {
  if (!swiper.slides[index])
    return;
  const imageEl = swiper.slides[index].querySelector('[loading="lazy"]');
  if (imageEl)
    imageEl.removeAttribute("loading");
};
var preload = (swiper) => {
  if (!swiper || swiper.destroyed || !swiper.params)
    return;
  let amount = swiper.params.lazyPreloadPrevNext;
  const len = swiper.slides.length;
  if (!len || !amount || amount < 0)
    return;
  amount = Math.min(amount, len);
  const slidesPerView = swiper.params.slidesPerView === "auto" ? swiper.slidesPerViewDynamic() : Math.ceil(swiper.params.slidesPerView);
  const activeIndex = swiper.activeIndex;
  if (swiper.params.grid && swiper.params.grid.rows > 1) {
    const activeColumn = activeIndex;
    const preloadColumns = [activeColumn - amount];
    preloadColumns.push(...Array.from({
      length: amount
    }).map((_, i) => {
      return activeColumn + slidesPerView + i;
    }));
    swiper.slides.forEach((slideEl, i) => {
      if (preloadColumns.includes(slideEl.column))
        unlazy(swiper, i);
    });
    return;
  }
  const slideIndexLastInView = activeIndex + slidesPerView - 1;
  if (swiper.params.rewind || swiper.params.loop) {
    for (let i = activeIndex - amount; i <= slideIndexLastInView + amount; i += 1) {
      const realIndex = (i % len + len) % len;
      if (realIndex < activeIndex || realIndex > slideIndexLastInView)
        unlazy(swiper, realIndex);
    }
  } else {
    for (let i = Math.max(activeIndex - amount, 0); i <= Math.min(slideIndexLastInView + amount, len - 1); i += 1) {
      if (i !== activeIndex && (i > slideIndexLastInView || i < activeIndex)) {
        unlazy(swiper, i);
      }
    }
  }
};
function getActiveIndexByTranslate(swiper) {
  const {
    slidesGrid,
    params
  } = swiper;
  const translate2 = swiper.rtlTranslate ? swiper.translate : -swiper.translate;
  let activeIndex;
  for (let i = 0; i < slidesGrid.length; i += 1) {
    if (typeof slidesGrid[i + 1] !== "undefined") {
      if (translate2 >= slidesGrid[i] && translate2 < slidesGrid[i + 1] - (slidesGrid[i + 1] - slidesGrid[i]) / 2) {
        activeIndex = i;
      } else if (translate2 >= slidesGrid[i] && translate2 < slidesGrid[i + 1]) {
        activeIndex = i + 1;
      }
    } else if (translate2 >= slidesGrid[i]) {
      activeIndex = i;
    }
  }
  if (params.normalizeSlideIndex) {
    if (activeIndex < 0 || typeof activeIndex === "undefined")
      activeIndex = 0;
  }
  return activeIndex;
}
function updateActiveIndex(newActiveIndex) {
  const swiper = this;
  const translate2 = swiper.rtlTranslate ? swiper.translate : -swiper.translate;
  const {
    snapGrid,
    params,
    activeIndex: previousIndex,
    realIndex: previousRealIndex,
    snapIndex: previousSnapIndex
  } = swiper;
  let activeIndex = newActiveIndex;
  let snapIndex;
  const getVirtualRealIndex = (aIndex) => {
    let realIndex2 = aIndex - swiper.virtual.slidesBefore;
    if (realIndex2 < 0) {
      realIndex2 = swiper.virtual.slides.length + realIndex2;
    }
    if (realIndex2 >= swiper.virtual.slides.length) {
      realIndex2 -= swiper.virtual.slides.length;
    }
    return realIndex2;
  };
  if (typeof activeIndex === "undefined") {
    activeIndex = getActiveIndexByTranslate(swiper);
  }
  if (snapGrid.indexOf(translate2) >= 0) {
    snapIndex = snapGrid.indexOf(translate2);
  } else {
    const skip = Math.min(params.slidesPerGroupSkip, activeIndex);
    snapIndex = skip + Math.floor((activeIndex - skip) / params.slidesPerGroup);
  }
  if (snapIndex >= snapGrid.length)
    snapIndex = snapGrid.length - 1;
  if (activeIndex === previousIndex && !swiper.params.loop) {
    if (snapIndex !== previousSnapIndex) {
      swiper.snapIndex = snapIndex;
      swiper.emit("snapIndexChange");
    }
    return;
  }
  if (activeIndex === previousIndex && swiper.params.loop && swiper.virtual && swiper.params.virtual.enabled) {
    swiper.realIndex = getVirtualRealIndex(activeIndex);
    return;
  }
  const gridEnabled = swiper.grid && params.grid && params.grid.rows > 1;
  let realIndex;
  if (swiper.virtual && params.virtual.enabled && params.loop) {
    realIndex = getVirtualRealIndex(activeIndex);
  } else if (gridEnabled) {
    const firstSlideInColumn = swiper.slides.filter((slideEl) => slideEl.column === activeIndex)[0];
    let activeSlideIndex = parseInt(firstSlideInColumn.getAttribute("data-swiper-slide-index"), 10);
    if (Number.isNaN(activeSlideIndex)) {
      activeSlideIndex = Math.max(swiper.slides.indexOf(firstSlideInColumn), 0);
    }
    realIndex = Math.floor(activeSlideIndex / params.grid.rows);
  } else if (swiper.slides[activeIndex]) {
    const slideIndex = swiper.slides[activeIndex].getAttribute("data-swiper-slide-index");
    if (slideIndex) {
      realIndex = parseInt(slideIndex, 10);
    } else {
      realIndex = activeIndex;
    }
  } else {
    realIndex = activeIndex;
  }
  Object.assign(swiper, {
    previousSnapIndex,
    snapIndex,
    previousRealIndex,
    realIndex,
    previousIndex,
    activeIndex
  });
  if (swiper.initialized) {
    preload(swiper);
  }
  swiper.emit("activeIndexChange");
  swiper.emit("snapIndexChange");
  if (swiper.initialized || swiper.params.runCallbacksOnInit) {
    if (previousRealIndex !== realIndex) {
      swiper.emit("realIndexChange");
    }
    swiper.emit("slideChange");
  }
}
function updateClickedSlide(el, path) {
  const swiper = this;
  const params = swiper.params;
  let slide2 = el.closest(`.${params.slideClass}, swiper-slide`);
  if (!slide2 && swiper.isElement && path && path.length > 1 && path.includes(el)) {
    [...path.slice(path.indexOf(el) + 1, path.length)].forEach((pathEl) => {
      if (!slide2 && pathEl.matches && pathEl.matches(`.${params.slideClass}, swiper-slide`)) {
        slide2 = pathEl;
      }
    });
  }
  let slideFound = false;
  let slideIndex;
  if (slide2) {
    for (let i = 0; i < swiper.slides.length; i += 1) {
      if (swiper.slides[i] === slide2) {
        slideFound = true;
        slideIndex = i;
        break;
      }
    }
  }
  if (slide2 && slideFound) {
    swiper.clickedSlide = slide2;
    if (swiper.virtual && swiper.params.virtual.enabled) {
      swiper.clickedIndex = parseInt(slide2.getAttribute("data-swiper-slide-index"), 10);
    } else {
      swiper.clickedIndex = slideIndex;
    }
  } else {
    swiper.clickedSlide = void 0;
    swiper.clickedIndex = void 0;
    return;
  }
  if (params.slideToClickedSlide && swiper.clickedIndex !== void 0 && swiper.clickedIndex !== swiper.activeIndex) {
    swiper.slideToClickedSlide();
  }
}
var update = {
  updateSize,
  updateSlides,
  updateAutoHeight,
  updateSlidesOffset,
  updateSlidesProgress,
  updateProgress,
  updateSlidesClasses,
  updateActiveIndex,
  updateClickedSlide
};
function getSwiperTranslate(axis) {
  if (axis === void 0) {
    axis = this.isHorizontal() ? "x" : "y";
  }
  const swiper = this;
  const {
    params,
    rtlTranslate: rtl,
    translate: translate2,
    wrapperEl
  } = swiper;
  if (params.virtualTranslate) {
    return rtl ? -translate2 : translate2;
  }
  if (params.cssMode) {
    return translate2;
  }
  let currentTranslate = getTranslate(wrapperEl, axis);
  currentTranslate += swiper.cssOverflowAdjustment();
  if (rtl)
    currentTranslate = -currentTranslate;
  return currentTranslate || 0;
}
function setTranslate(translate2, byController) {
  const swiper = this;
  const {
    rtlTranslate: rtl,
    params,
    wrapperEl,
    progress
  } = swiper;
  let x = 0;
  let y = 0;
  const z = 0;
  if (swiper.isHorizontal()) {
    x = rtl ? -translate2 : translate2;
  } else {
    y = translate2;
  }
  if (params.roundLengths) {
    x = Math.floor(x);
    y = Math.floor(y);
  }
  swiper.previousTranslate = swiper.translate;
  swiper.translate = swiper.isHorizontal() ? x : y;
  if (params.cssMode) {
    wrapperEl[swiper.isHorizontal() ? "scrollLeft" : "scrollTop"] = swiper.isHorizontal() ? -x : -y;
  } else if (!params.virtualTranslate) {
    if (swiper.isHorizontal()) {
      x -= swiper.cssOverflowAdjustment();
    } else {
      y -= swiper.cssOverflowAdjustment();
    }
    wrapperEl.style.transform = `translate3d(${x}px, ${y}px, ${z}px)`;
  }
  let newProgress;
  const translatesDiff = swiper.maxTranslate() - swiper.minTranslate();
  if (translatesDiff === 0) {
    newProgress = 0;
  } else {
    newProgress = (translate2 - swiper.minTranslate()) / translatesDiff;
  }
  if (newProgress !== progress) {
    swiper.updateProgress(translate2);
  }
  swiper.emit("setTranslate", swiper.translate, byController);
}
function minTranslate() {
  return -this.snapGrid[0];
}
function maxTranslate() {
  return -this.snapGrid[this.snapGrid.length - 1];
}
function translateTo(translate2, speed, runCallbacks, translateBounds, internal) {
  if (translate2 === void 0) {
    translate2 = 0;
  }
  if (speed === void 0) {
    speed = this.params.speed;
  }
  if (runCallbacks === void 0) {
    runCallbacks = true;
  }
  if (translateBounds === void 0) {
    translateBounds = true;
  }
  const swiper = this;
  const {
    params,
    wrapperEl
  } = swiper;
  if (swiper.animating && params.preventInteractionOnTransition) {
    return false;
  }
  const minTranslate2 = swiper.minTranslate();
  const maxTranslate2 = swiper.maxTranslate();
  let newTranslate;
  if (translateBounds && translate2 > minTranslate2)
    newTranslate = minTranslate2;
  else if (translateBounds && translate2 < maxTranslate2)
    newTranslate = maxTranslate2;
  else
    newTranslate = translate2;
  swiper.updateProgress(newTranslate);
  if (params.cssMode) {
    const isH = swiper.isHorizontal();
    if (speed === 0) {
      wrapperEl[isH ? "scrollLeft" : "scrollTop"] = -newTranslate;
    } else {
      if (!swiper.support.smoothScroll) {
        animateCSSModeScroll({
          swiper,
          targetPosition: -newTranslate,
          side: isH ? "left" : "top"
        });
        return true;
      }
      wrapperEl.scrollTo({
        [isH ? "left" : "top"]: -newTranslate,
        behavior: "smooth"
      });
    }
    return true;
  }
  if (speed === 0) {
    swiper.setTransition(0);
    swiper.setTranslate(newTranslate);
    if (runCallbacks) {
      swiper.emit("beforeTransitionStart", speed, internal);
      swiper.emit("transitionEnd");
    }
  } else {
    swiper.setTransition(speed);
    swiper.setTranslate(newTranslate);
    if (runCallbacks) {
      swiper.emit("beforeTransitionStart", speed, internal);
      swiper.emit("transitionStart");
    }
    if (!swiper.animating) {
      swiper.animating = true;
      if (!swiper.onTranslateToWrapperTransitionEnd) {
        swiper.onTranslateToWrapperTransitionEnd = function transitionEnd2(e) {
          if (!swiper || swiper.destroyed)
            return;
          if (e.target !== this)
            return;
          swiper.wrapperEl.removeEventListener("transitionend", swiper.onTranslateToWrapperTransitionEnd);
          swiper.onTranslateToWrapperTransitionEnd = null;
          delete swiper.onTranslateToWrapperTransitionEnd;
          swiper.animating = false;
          if (runCallbacks) {
            swiper.emit("transitionEnd");
          }
        };
      }
      swiper.wrapperEl.addEventListener("transitionend", swiper.onTranslateToWrapperTransitionEnd);
    }
  }
  return true;
}
var translate = {
  getTranslate: getSwiperTranslate,
  setTranslate,
  minTranslate,
  maxTranslate,
  translateTo
};
function setTransition(duration, byController) {
  const swiper = this;
  if (!swiper.params.cssMode) {
    swiper.wrapperEl.style.transitionDuration = `${duration}ms`;
    swiper.wrapperEl.style.transitionDelay = duration === 0 ? `0ms` : "";
  }
  swiper.emit("setTransition", duration, byController);
}
function transitionEmit(_ref) {
  let {
    swiper,
    runCallbacks,
    direction,
    step
  } = _ref;
  const {
    activeIndex,
    previousIndex
  } = swiper;
  let dir = direction;
  if (!dir) {
    if (activeIndex > previousIndex)
      dir = "next";
    else if (activeIndex < previousIndex)
      dir = "prev";
    else
      dir = "reset";
  }
  swiper.emit(`transition${step}`);
  if (runCallbacks && activeIndex !== previousIndex) {
    if (dir === "reset") {
      swiper.emit(`slideResetTransition${step}`);
      return;
    }
    swiper.emit(`slideChangeTransition${step}`);
    if (dir === "next") {
      swiper.emit(`slideNextTransition${step}`);
    } else {
      swiper.emit(`slidePrevTransition${step}`);
    }
  }
}
function transitionStart(runCallbacks, direction) {
  if (runCallbacks === void 0) {
    runCallbacks = true;
  }
  const swiper = this;
  const {
    params
  } = swiper;
  if (params.cssMode)
    return;
  if (params.autoHeight) {
    swiper.updateAutoHeight();
  }
  transitionEmit({
    swiper,
    runCallbacks,
    direction,
    step: "Start"
  });
}
function transitionEnd(runCallbacks, direction) {
  if (runCallbacks === void 0) {
    runCallbacks = true;
  }
  const swiper = this;
  const {
    params
  } = swiper;
  swiper.animating = false;
  if (params.cssMode)
    return;
  swiper.setTransition(0);
  transitionEmit({
    swiper,
    runCallbacks,
    direction,
    step: "End"
  });
}
var transition = {
  setTransition,
  transitionStart,
  transitionEnd
};
function slideTo(index, speed, runCallbacks, internal, initial) {
  if (index === void 0) {
    index = 0;
  }
  if (runCallbacks === void 0) {
    runCallbacks = true;
  }
  if (typeof index === "string") {
    index = parseInt(index, 10);
  }
  const swiper = this;
  let slideIndex = index;
  if (slideIndex < 0)
    slideIndex = 0;
  const {
    params,
    snapGrid,
    slidesGrid,
    previousIndex,
    activeIndex,
    rtlTranslate: rtl,
    wrapperEl,
    enabled
  } = swiper;
  if (!enabled && !internal && !initial || swiper.destroyed || swiper.animating && params.preventInteractionOnTransition) {
    return false;
  }
  if (typeof speed === "undefined") {
    speed = swiper.params.speed;
  }
  const skip = Math.min(swiper.params.slidesPerGroupSkip, slideIndex);
  let snapIndex = skip + Math.floor((slideIndex - skip) / swiper.params.slidesPerGroup);
  if (snapIndex >= snapGrid.length)
    snapIndex = snapGrid.length - 1;
  const translate2 = -snapGrid[snapIndex];
  if (params.normalizeSlideIndex) {
    for (let i = 0; i < slidesGrid.length; i += 1) {
      const normalizedTranslate = -Math.floor(translate2 * 100);
      const normalizedGrid = Math.floor(slidesGrid[i] * 100);
      const normalizedGridNext = Math.floor(slidesGrid[i + 1] * 100);
      if (typeof slidesGrid[i + 1] !== "undefined") {
        if (normalizedTranslate >= normalizedGrid && normalizedTranslate < normalizedGridNext - (normalizedGridNext - normalizedGrid) / 2) {
          slideIndex = i;
        } else if (normalizedTranslate >= normalizedGrid && normalizedTranslate < normalizedGridNext) {
          slideIndex = i + 1;
        }
      } else if (normalizedTranslate >= normalizedGrid) {
        slideIndex = i;
      }
    }
  }
  if (swiper.initialized && slideIndex !== activeIndex) {
    if (!swiper.allowSlideNext && (rtl ? translate2 > swiper.translate && translate2 > swiper.minTranslate() : translate2 < swiper.translate && translate2 < swiper.minTranslate())) {
      return false;
    }
    if (!swiper.allowSlidePrev && translate2 > swiper.translate && translate2 > swiper.maxTranslate()) {
      if ((activeIndex || 0) !== slideIndex) {
        return false;
      }
    }
  }
  if (slideIndex !== (previousIndex || 0) && runCallbacks) {
    swiper.emit("beforeSlideChangeStart");
  }
  swiper.updateProgress(translate2);
  let direction;
  if (slideIndex > activeIndex)
    direction = "next";
  else if (slideIndex < activeIndex)
    direction = "prev";
  else
    direction = "reset";
  const isVirtual = swiper.virtual && swiper.params.virtual.enabled;
  const isInitialVirtual = isVirtual && initial;
  if (!isInitialVirtual && (rtl && -translate2 === swiper.translate || !rtl && translate2 === swiper.translate)) {
    swiper.updateActiveIndex(slideIndex);
    if (params.autoHeight) {
      swiper.updateAutoHeight();
    }
    swiper.updateSlidesClasses();
    if (params.effect !== "slide") {
      swiper.setTranslate(translate2);
    }
    if (direction !== "reset") {
      swiper.transitionStart(runCallbacks, direction);
      swiper.transitionEnd(runCallbacks, direction);
    }
    return false;
  }
  if (params.cssMode) {
    const isH = swiper.isHorizontal();
    const t = rtl ? translate2 : -translate2;
    if (speed === 0) {
      if (isVirtual) {
        swiper.wrapperEl.style.scrollSnapType = "none";
        swiper._immediateVirtual = true;
      }
      if (isVirtual && !swiper._cssModeVirtualInitialSet && swiper.params.initialSlide > 0) {
        swiper._cssModeVirtualInitialSet = true;
        requestAnimationFrame(() => {
          wrapperEl[isH ? "scrollLeft" : "scrollTop"] = t;
        });
      } else {
        wrapperEl[isH ? "scrollLeft" : "scrollTop"] = t;
      }
      if (isVirtual) {
        requestAnimationFrame(() => {
          swiper.wrapperEl.style.scrollSnapType = "";
          swiper._immediateVirtual = false;
        });
      }
    } else {
      if (!swiper.support.smoothScroll) {
        animateCSSModeScroll({
          swiper,
          targetPosition: t,
          side: isH ? "left" : "top"
        });
        return true;
      }
      wrapperEl.scrollTo({
        [isH ? "left" : "top"]: t,
        behavior: "smooth"
      });
    }
    return true;
  }
  swiper.setTransition(speed);
  swiper.setTranslate(translate2);
  swiper.updateActiveIndex(slideIndex);
  swiper.updateSlidesClasses();
  swiper.emit("beforeTransitionStart", speed, internal);
  swiper.transitionStart(runCallbacks, direction);
  if (speed === 0) {
    swiper.transitionEnd(runCallbacks, direction);
  } else if (!swiper.animating) {
    swiper.animating = true;
    if (!swiper.onSlideToWrapperTransitionEnd) {
      swiper.onSlideToWrapperTransitionEnd = function transitionEnd2(e) {
        if (!swiper || swiper.destroyed)
          return;
        if (e.target !== this)
          return;
        swiper.wrapperEl.removeEventListener("transitionend", swiper.onSlideToWrapperTransitionEnd);
        swiper.onSlideToWrapperTransitionEnd = null;
        delete swiper.onSlideToWrapperTransitionEnd;
        swiper.transitionEnd(runCallbacks, direction);
      };
    }
    swiper.wrapperEl.addEventListener("transitionend", swiper.onSlideToWrapperTransitionEnd);
  }
  return true;
}
function slideToLoop(index, speed, runCallbacks, internal) {
  if (index === void 0) {
    index = 0;
  }
  if (runCallbacks === void 0) {
    runCallbacks = true;
  }
  if (typeof index === "string") {
    const indexAsNumber = parseInt(index, 10);
    index = indexAsNumber;
  }
  const swiper = this;
  if (swiper.destroyed)
    return;
  if (typeof speed === "undefined") {
    speed = swiper.params.speed;
  }
  const gridEnabled = swiper.grid && swiper.params.grid && swiper.params.grid.rows > 1;
  let newIndex = index;
  if (swiper.params.loop) {
    if (swiper.virtual && swiper.params.virtual.enabled) {
      newIndex = newIndex + swiper.virtual.slidesBefore;
    } else {
      let targetSlideIndex;
      if (gridEnabled) {
        const slideIndex = newIndex * swiper.params.grid.rows;
        targetSlideIndex = swiper.slides.filter((slideEl) => slideEl.getAttribute("data-swiper-slide-index") * 1 === slideIndex)[0].column;
      } else {
        targetSlideIndex = swiper.getSlideIndexByData(newIndex);
      }
      const cols = gridEnabled ? Math.ceil(swiper.slides.length / swiper.params.grid.rows) : swiper.slides.length;
      const {
        centeredSlides
      } = swiper.params;
      let slidesPerView = swiper.params.slidesPerView;
      if (slidesPerView === "auto") {
        slidesPerView = swiper.slidesPerViewDynamic();
      } else {
        slidesPerView = Math.ceil(parseFloat(swiper.params.slidesPerView, 10));
        if (centeredSlides && slidesPerView % 2 === 0) {
          slidesPerView = slidesPerView + 1;
        }
      }
      let needLoopFix = cols - targetSlideIndex < slidesPerView;
      if (centeredSlides) {
        needLoopFix = needLoopFix || targetSlideIndex < Math.ceil(slidesPerView / 2);
      }
      if (internal && centeredSlides && swiper.params.slidesPerView !== "auto" && !gridEnabled) {
        needLoopFix = false;
      }
      if (needLoopFix) {
        const direction = centeredSlides ? targetSlideIndex < swiper.activeIndex ? "prev" : "next" : targetSlideIndex - swiper.activeIndex - 1 < swiper.params.slidesPerView ? "next" : "prev";
        swiper.loopFix({
          direction,
          slideTo: true,
          activeSlideIndex: direction === "next" ? targetSlideIndex + 1 : targetSlideIndex - cols + 1,
          slideRealIndex: direction === "next" ? swiper.realIndex : void 0
        });
      }
      if (gridEnabled) {
        const slideIndex = newIndex * swiper.params.grid.rows;
        newIndex = swiper.slides.filter((slideEl) => slideEl.getAttribute("data-swiper-slide-index") * 1 === slideIndex)[0].column;
      } else {
        newIndex = swiper.getSlideIndexByData(newIndex);
      }
    }
  }
  requestAnimationFrame(() => {
    swiper.slideTo(newIndex, speed, runCallbacks, internal);
  });
  return swiper;
}
function slideNext(speed, runCallbacks, internal) {
  if (runCallbacks === void 0) {
    runCallbacks = true;
  }
  const swiper = this;
  const {
    enabled,
    params,
    animating
  } = swiper;
  if (!enabled || swiper.destroyed)
    return swiper;
  if (typeof speed === "undefined") {
    speed = swiper.params.speed;
  }
  let perGroup = params.slidesPerGroup;
  if (params.slidesPerView === "auto" && params.slidesPerGroup === 1 && params.slidesPerGroupAuto) {
    perGroup = Math.max(swiper.slidesPerViewDynamic("current", true), 1);
  }
  const increment = swiper.activeIndex < params.slidesPerGroupSkip ? 1 : perGroup;
  const isVirtual = swiper.virtual && params.virtual.enabled;
  if (params.loop) {
    if (animating && !isVirtual && params.loopPreventsSliding)
      return false;
    swiper.loopFix({
      direction: "next"
    });
    swiper._clientLeft = swiper.wrapperEl.clientLeft;
    if (swiper.activeIndex === swiper.slides.length - 1 && params.cssMode) {
      requestAnimationFrame(() => {
        swiper.slideTo(swiper.activeIndex + increment, speed, runCallbacks, internal);
      });
      return true;
    }
  }
  if (params.rewind && swiper.isEnd) {
    return swiper.slideTo(0, speed, runCallbacks, internal);
  }
  return swiper.slideTo(swiper.activeIndex + increment, speed, runCallbacks, internal);
}
function slidePrev(speed, runCallbacks, internal) {
  if (runCallbacks === void 0) {
    runCallbacks = true;
  }
  const swiper = this;
  const {
    params,
    snapGrid,
    slidesGrid,
    rtlTranslate,
    enabled,
    animating
  } = swiper;
  if (!enabled || swiper.destroyed)
    return swiper;
  if (typeof speed === "undefined") {
    speed = swiper.params.speed;
  }
  const isVirtual = swiper.virtual && params.virtual.enabled;
  if (params.loop) {
    if (animating && !isVirtual && params.loopPreventsSliding)
      return false;
    swiper.loopFix({
      direction: "prev"
    });
    swiper._clientLeft = swiper.wrapperEl.clientLeft;
  }
  const translate2 = rtlTranslate ? swiper.translate : -swiper.translate;
  function normalize(val) {
    if (val < 0)
      return -Math.floor(Math.abs(val));
    return Math.floor(val);
  }
  const normalizedTranslate = normalize(translate2);
  const normalizedSnapGrid = snapGrid.map((val) => normalize(val));
  let prevSnap = snapGrid[normalizedSnapGrid.indexOf(normalizedTranslate) - 1];
  if (typeof prevSnap === "undefined" && params.cssMode) {
    let prevSnapIndex;
    snapGrid.forEach((snap, snapIndex) => {
      if (normalizedTranslate >= snap) {
        prevSnapIndex = snapIndex;
      }
    });
    if (typeof prevSnapIndex !== "undefined") {
      prevSnap = snapGrid[prevSnapIndex > 0 ? prevSnapIndex - 1 : prevSnapIndex];
    }
  }
  let prevIndex = 0;
  if (typeof prevSnap !== "undefined") {
    prevIndex = slidesGrid.indexOf(prevSnap);
    if (prevIndex < 0)
      prevIndex = swiper.activeIndex - 1;
    if (params.slidesPerView === "auto" && params.slidesPerGroup === 1 && params.slidesPerGroupAuto) {
      prevIndex = prevIndex - swiper.slidesPerViewDynamic("previous", true) + 1;
      prevIndex = Math.max(prevIndex, 0);
    }
  }
  if (params.rewind && swiper.isBeginning) {
    const lastIndex = swiper.params.virtual && swiper.params.virtual.enabled && swiper.virtual ? swiper.virtual.slides.length - 1 : swiper.slides.length - 1;
    return swiper.slideTo(lastIndex, speed, runCallbacks, internal);
  } else if (params.loop && swiper.activeIndex === 0 && params.cssMode) {
    requestAnimationFrame(() => {
      swiper.slideTo(prevIndex, speed, runCallbacks, internal);
    });
    return true;
  }
  return swiper.slideTo(prevIndex, speed, runCallbacks, internal);
}
function slideReset(speed, runCallbacks, internal) {
  if (runCallbacks === void 0) {
    runCallbacks = true;
  }
  const swiper = this;
  if (swiper.destroyed)
    return;
  if (typeof speed === "undefined") {
    speed = swiper.params.speed;
  }
  return swiper.slideTo(swiper.activeIndex, speed, runCallbacks, internal);
}
function slideToClosest(speed, runCallbacks, internal, threshold) {
  if (runCallbacks === void 0) {
    runCallbacks = true;
  }
  if (threshold === void 0) {
    threshold = 0.5;
  }
  const swiper = this;
  if (swiper.destroyed)
    return;
  if (typeof speed === "undefined") {
    speed = swiper.params.speed;
  }
  let index = swiper.activeIndex;
  const skip = Math.min(swiper.params.slidesPerGroupSkip, index);
  const snapIndex = skip + Math.floor((index - skip) / swiper.params.slidesPerGroup);
  const translate2 = swiper.rtlTranslate ? swiper.translate : -swiper.translate;
  if (translate2 >= swiper.snapGrid[snapIndex]) {
    const currentSnap = swiper.snapGrid[snapIndex];
    const nextSnap = swiper.snapGrid[snapIndex + 1];
    if (translate2 - currentSnap > (nextSnap - currentSnap) * threshold) {
      index += swiper.params.slidesPerGroup;
    }
  } else {
    const prevSnap = swiper.snapGrid[snapIndex - 1];
    const currentSnap = swiper.snapGrid[snapIndex];
    if (translate2 - prevSnap <= (currentSnap - prevSnap) * threshold) {
      index -= swiper.params.slidesPerGroup;
    }
  }
  index = Math.max(index, 0);
  index = Math.min(index, swiper.slidesGrid.length - 1);
  return swiper.slideTo(index, speed, runCallbacks, internal);
}
function slideToClickedSlide() {
  const swiper = this;
  if (swiper.destroyed)
    return;
  const {
    params,
    slidesEl
  } = swiper;
  const slidesPerView = params.slidesPerView === "auto" ? swiper.slidesPerViewDynamic() : params.slidesPerView;
  let slideToIndex = swiper.clickedIndex;
  let realIndex;
  const slideSelector = swiper.isElement ? `swiper-slide` : `.${params.slideClass}`;
  if (params.loop) {
    if (swiper.animating)
      return;
    realIndex = parseInt(swiper.clickedSlide.getAttribute("data-swiper-slide-index"), 10);
    if (params.centeredSlides) {
      if (slideToIndex < swiper.loopedSlides - slidesPerView / 2 || slideToIndex > swiper.slides.length - swiper.loopedSlides + slidesPerView / 2) {
        swiper.loopFix();
        slideToIndex = swiper.getSlideIndex(elementChildren(slidesEl, `${slideSelector}[data-swiper-slide-index="${realIndex}"]`)[0]);
        nextTick(() => {
          swiper.slideTo(slideToIndex);
        });
      } else {
        swiper.slideTo(slideToIndex);
      }
    } else if (slideToIndex > swiper.slides.length - slidesPerView) {
      swiper.loopFix();
      slideToIndex = swiper.getSlideIndex(elementChildren(slidesEl, `${slideSelector}[data-swiper-slide-index="${realIndex}"]`)[0]);
      nextTick(() => {
        swiper.slideTo(slideToIndex);
      });
    } else {
      swiper.slideTo(slideToIndex);
    }
  } else {
    swiper.slideTo(slideToIndex);
  }
}
var slide = {
  slideTo,
  slideToLoop,
  slideNext,
  slidePrev,
  slideReset,
  slideToClosest,
  slideToClickedSlide
};
function loopCreate(slideRealIndex) {
  const swiper = this;
  const {
    params,
    slidesEl
  } = swiper;
  if (!params.loop || swiper.virtual && swiper.params.virtual.enabled)
    return;
  const initSlides = () => {
    const slides = elementChildren(slidesEl, `.${params.slideClass}, swiper-slide`);
    slides.forEach((el, index) => {
      el.setAttribute("data-swiper-slide-index", index);
    });
  };
  const gridEnabled = swiper.grid && params.grid && params.grid.rows > 1;
  const slidesPerGroup = params.slidesPerGroup * (gridEnabled ? params.grid.rows : 1);
  const shouldFillGroup = swiper.slides.length % slidesPerGroup !== 0;
  const shouldFillGrid = gridEnabled && swiper.slides.length % params.grid.rows !== 0;
  const addBlankSlides = (amountOfSlides) => {
    for (let i = 0; i < amountOfSlides; i += 1) {
      const slideEl = swiper.isElement ? createElement("swiper-slide", [params.slideBlankClass]) : createElement("div", [params.slideClass, params.slideBlankClass]);
      swiper.slidesEl.append(slideEl);
    }
  };
  if (shouldFillGroup) {
    if (params.loopAddBlankSlides) {
      const slidesToAdd = slidesPerGroup - swiper.slides.length % slidesPerGroup;
      addBlankSlides(slidesToAdd);
      swiper.recalcSlides();
      swiper.updateSlides();
    } else {
      showWarning("Swiper Loop Warning: The number of slides is not even to slidesPerGroup, loop mode may not function properly. You need to add more slides (or make duplicates, or empty slides)");
    }
    initSlides();
  } else if (shouldFillGrid) {
    if (params.loopAddBlankSlides) {
      const slidesToAdd = params.grid.rows - swiper.slides.length % params.grid.rows;
      addBlankSlides(slidesToAdd);
      swiper.recalcSlides();
      swiper.updateSlides();
    } else {
      showWarning("Swiper Loop Warning: The number of slides is not even to grid.rows, loop mode may not function properly. You need to add more slides (or make duplicates, or empty slides)");
    }
    initSlides();
  } else {
    initSlides();
  }
  swiper.loopFix({
    slideRealIndex,
    direction: params.centeredSlides ? void 0 : "next"
  });
}
function loopFix(_temp) {
  let {
    slideRealIndex,
    slideTo: slideTo2 = true,
    direction,
    setTranslate: setTranslate2,
    activeSlideIndex,
    byController,
    byMousewheel
  } = _temp === void 0 ? {} : _temp;
  const swiper = this;
  if (!swiper.params.loop)
    return;
  swiper.emit("beforeLoopFix");
  const {
    slides,
    allowSlidePrev,
    allowSlideNext,
    slidesEl,
    params
  } = swiper;
  const {
    centeredSlides
  } = params;
  swiper.allowSlidePrev = true;
  swiper.allowSlideNext = true;
  if (swiper.virtual && params.virtual.enabled) {
    if (slideTo2) {
      if (!params.centeredSlides && swiper.snapIndex === 0) {
        swiper.slideTo(swiper.virtual.slides.length, 0, false, true);
      } else if (params.centeredSlides && swiper.snapIndex < params.slidesPerView) {
        swiper.slideTo(swiper.virtual.slides.length + swiper.snapIndex, 0, false, true);
      } else if (swiper.snapIndex === swiper.snapGrid.length - 1) {
        swiper.slideTo(swiper.virtual.slidesBefore, 0, false, true);
      }
    }
    swiper.allowSlidePrev = allowSlidePrev;
    swiper.allowSlideNext = allowSlideNext;
    swiper.emit("loopFix");
    return;
  }
  let slidesPerView = params.slidesPerView;
  if (slidesPerView === "auto") {
    slidesPerView = swiper.slidesPerViewDynamic();
  } else {
    slidesPerView = Math.ceil(parseFloat(params.slidesPerView, 10));
    if (centeredSlides && slidesPerView % 2 === 0) {
      slidesPerView = slidesPerView + 1;
    }
  }
  const slidesPerGroup = params.slidesPerGroupAuto ? slidesPerView : params.slidesPerGroup;
  let loopedSlides = slidesPerGroup;
  if (loopedSlides % slidesPerGroup !== 0) {
    loopedSlides += slidesPerGroup - loopedSlides % slidesPerGroup;
  }
  loopedSlides += params.loopAdditionalSlides;
  swiper.loopedSlides = loopedSlides;
  const gridEnabled = swiper.grid && params.grid && params.grid.rows > 1;
  if (slides.length < slidesPerView + loopedSlides) {
    showWarning("Swiper Loop Warning: The number of slides is not enough for loop mode, it will be disabled and not function properly. You need to add more slides (or make duplicates) or lower the values of slidesPerView and slidesPerGroup parameters");
  } else if (gridEnabled && params.grid.fill === "row") {
    showWarning("Swiper Loop Warning: Loop mode is not compatible with grid.fill = `row`");
  }
  const prependSlidesIndexes = [];
  const appendSlidesIndexes = [];
  let activeIndex = swiper.activeIndex;
  if (typeof activeSlideIndex === "undefined") {
    activeSlideIndex = swiper.getSlideIndex(slides.filter((el) => el.classList.contains(params.slideActiveClass))[0]);
  } else {
    activeIndex = activeSlideIndex;
  }
  const isNext = direction === "next" || !direction;
  const isPrev = direction === "prev" || !direction;
  let slidesPrepended = 0;
  let slidesAppended = 0;
  const cols = gridEnabled ? Math.ceil(slides.length / params.grid.rows) : slides.length;
  const activeColIndex = gridEnabled ? slides[activeSlideIndex].column : activeSlideIndex;
  const activeColIndexWithShift = activeColIndex + (centeredSlides && typeof setTranslate2 === "undefined" ? -slidesPerView / 2 + 0.5 : 0);
  if (activeColIndexWithShift < loopedSlides) {
    slidesPrepended = Math.max(loopedSlides - activeColIndexWithShift, slidesPerGroup);
    for (let i = 0; i < loopedSlides - activeColIndexWithShift; i += 1) {
      const index = i - Math.floor(i / cols) * cols;
      if (gridEnabled) {
        const colIndexToPrepend = cols - index - 1;
        for (let i2 = slides.length - 1; i2 >= 0; i2 -= 1) {
          if (slides[i2].column === colIndexToPrepend)
            prependSlidesIndexes.push(i2);
        }
      } else {
        prependSlidesIndexes.push(cols - index - 1);
      }
    }
  } else if (activeColIndexWithShift + slidesPerView > cols - loopedSlides) {
    slidesAppended = Math.max(activeColIndexWithShift - (cols - loopedSlides * 2), slidesPerGroup);
    for (let i = 0; i < slidesAppended; i += 1) {
      const index = i - Math.floor(i / cols) * cols;
      if (gridEnabled) {
        slides.forEach((slide2, slideIndex) => {
          if (slide2.column === index)
            appendSlidesIndexes.push(slideIndex);
        });
      } else {
        appendSlidesIndexes.push(index);
      }
    }
  }
  swiper.__preventObserver__ = true;
  requestAnimationFrame(() => {
    swiper.__preventObserver__ = false;
  });
  if (isPrev) {
    prependSlidesIndexes.forEach((index) => {
      slides[index].swiperLoopMoveDOM = true;
      slidesEl.prepend(slides[index]);
      slides[index].swiperLoopMoveDOM = false;
    });
  }
  if (isNext) {
    appendSlidesIndexes.forEach((index) => {
      slides[index].swiperLoopMoveDOM = true;
      slidesEl.append(slides[index]);
      slides[index].swiperLoopMoveDOM = false;
    });
  }
  swiper.recalcSlides();
  if (params.slidesPerView === "auto") {
    swiper.updateSlides();
  } else if (gridEnabled && (prependSlidesIndexes.length > 0 && isPrev || appendSlidesIndexes.length > 0 && isNext)) {
    swiper.slides.forEach((slide2, slideIndex) => {
      swiper.grid.updateSlide(slideIndex, slide2, swiper.slides);
    });
  }
  if (params.watchSlidesProgress) {
    swiper.updateSlidesOffset();
  }
  if (slideTo2) {
    if (prependSlidesIndexes.length > 0 && isPrev) {
      if (typeof slideRealIndex === "undefined") {
        const currentSlideTranslate = swiper.slidesGrid[activeIndex];
        const newSlideTranslate = swiper.slidesGrid[activeIndex + slidesPrepended];
        const diff = newSlideTranslate - currentSlideTranslate;
        if (byMousewheel) {
          swiper.setTranslate(swiper.translate - diff);
        } else {
          swiper.slideTo(activeIndex + Math.ceil(slidesPrepended), 0, false, true);
          if (setTranslate2) {
            swiper.touchEventsData.startTranslate = swiper.touchEventsData.startTranslate - diff;
            swiper.touchEventsData.currentTranslate = swiper.touchEventsData.currentTranslate - diff;
          }
        }
      } else {
        if (setTranslate2) {
          const shift = gridEnabled ? prependSlidesIndexes.length / params.grid.rows : prependSlidesIndexes.length;
          swiper.slideTo(swiper.activeIndex + shift, 0, false, true);
          swiper.touchEventsData.currentTranslate = swiper.translate;
        }
      }
    } else if (appendSlidesIndexes.length > 0 && isNext) {
      if (typeof slideRealIndex === "undefined") {
        const currentSlideTranslate = swiper.slidesGrid[activeIndex];
        const newSlideTranslate = swiper.slidesGrid[activeIndex - slidesAppended];
        const diff = newSlideTranslate - currentSlideTranslate;
        if (byMousewheel) {
          swiper.setTranslate(swiper.translate - diff);
        } else {
          swiper.slideTo(activeIndex - slidesAppended, 0, false, true);
          if (setTranslate2) {
            swiper.touchEventsData.startTranslate = swiper.touchEventsData.startTranslate - diff;
            swiper.touchEventsData.currentTranslate = swiper.touchEventsData.currentTranslate - diff;
          }
        }
      } else {
        const shift = gridEnabled ? appendSlidesIndexes.length / params.grid.rows : appendSlidesIndexes.length;
        swiper.slideTo(swiper.activeIndex - shift, 0, false, true);
      }
    }
  }
  swiper.allowSlidePrev = allowSlidePrev;
  swiper.allowSlideNext = allowSlideNext;
  if (swiper.controller && swiper.controller.control && !byController) {
    const loopParams = {
      slideRealIndex,
      direction,
      setTranslate: setTranslate2,
      activeSlideIndex,
      byController: true
    };
    if (Array.isArray(swiper.controller.control)) {
      swiper.controller.control.forEach((c) => {
        if (!c.destroyed && c.params.loop)
          c.loopFix({
            ...loopParams,
            slideTo: c.params.slidesPerView === params.slidesPerView ? slideTo2 : false
          });
      });
    } else if (swiper.controller.control instanceof swiper.constructor && swiper.controller.control.params.loop) {
      swiper.controller.control.loopFix({
        ...loopParams,
        slideTo: swiper.controller.control.params.slidesPerView === params.slidesPerView ? slideTo2 : false
      });
    }
  }
  swiper.emit("loopFix");
}
function loopDestroy() {
  const swiper = this;
  const {
    params,
    slidesEl
  } = swiper;
  if (!params.loop || swiper.virtual && swiper.params.virtual.enabled)
    return;
  swiper.recalcSlides();
  const newSlidesOrder = [];
  swiper.slides.forEach((slideEl) => {
    const index = typeof slideEl.swiperSlideIndex === "undefined" ? slideEl.getAttribute("data-swiper-slide-index") * 1 : slideEl.swiperSlideIndex;
    newSlidesOrder[index] = slideEl;
  });
  swiper.slides.forEach((slideEl) => {
    slideEl.removeAttribute("data-swiper-slide-index");
  });
  newSlidesOrder.forEach((slideEl) => {
    slidesEl.append(slideEl);
  });
  swiper.recalcSlides();
  swiper.slideTo(swiper.realIndex, 0);
}
var loop = {
  loopCreate,
  loopFix,
  loopDestroy
};
function setGrabCursor(moving) {
  const swiper = this;
  if (!swiper.params.simulateTouch || swiper.params.watchOverflow && swiper.isLocked || swiper.params.cssMode)
    return;
  const el = swiper.params.touchEventsTarget === "container" ? swiper.el : swiper.wrapperEl;
  if (swiper.isElement) {
    swiper.__preventObserver__ = true;
  }
  el.style.cursor = "move";
  el.style.cursor = moving ? "grabbing" : "grab";
  if (swiper.isElement) {
    requestAnimationFrame(() => {
      swiper.__preventObserver__ = false;
    });
  }
}
function unsetGrabCursor() {
  const swiper = this;
  if (swiper.params.watchOverflow && swiper.isLocked || swiper.params.cssMode) {
    return;
  }
  if (swiper.isElement) {
    swiper.__preventObserver__ = true;
  }
  swiper[swiper.params.touchEventsTarget === "container" ? "el" : "wrapperEl"].style.cursor = "";
  if (swiper.isElement) {
    requestAnimationFrame(() => {
      swiper.__preventObserver__ = false;
    });
  }
}
var grabCursor = {
  setGrabCursor,
  unsetGrabCursor
};
function closestElement(selector, base) {
  if (base === void 0) {
    base = this;
  }
  function __closestFrom(el) {
    if (!el || el === getDocument() || el === getWindow())
      return null;
    if (el.assignedSlot)
      el = el.assignedSlot;
    const found = el.closest(selector);
    if (!found && !el.getRootNode) {
      return null;
    }
    return found || __closestFrom(el.getRootNode().host);
  }
  return __closestFrom(base);
}
function preventEdgeSwipe(swiper, event2, startX) {
  const window2 = getWindow();
  const {
    params
  } = swiper;
  const edgeSwipeDetection = params.edgeSwipeDetection;
  const edgeSwipeThreshold = params.edgeSwipeThreshold;
  if (edgeSwipeDetection && (startX <= edgeSwipeThreshold || startX >= window2.innerWidth - edgeSwipeThreshold)) {
    if (edgeSwipeDetection === "prevent") {
      event2.preventDefault();
      return true;
    }
    return false;
  }
  return true;
}
function onTouchStart(event2) {
  const swiper = this;
  const document2 = getDocument();
  let e = event2;
  if (e.originalEvent)
    e = e.originalEvent;
  const data = swiper.touchEventsData;
  if (e.type === "pointerdown") {
    if (data.pointerId !== null && data.pointerId !== e.pointerId) {
      return;
    }
    data.pointerId = e.pointerId;
  } else if (e.type === "touchstart" && e.targetTouches.length === 1) {
    data.touchId = e.targetTouches[0].identifier;
  }
  if (e.type === "touchstart") {
    preventEdgeSwipe(swiper, e, e.targetTouches[0].pageX);
    return;
  }
  const {
    params,
    touches,
    enabled
  } = swiper;
  if (!enabled)
    return;
  if (!params.simulateTouch && e.pointerType === "mouse")
    return;
  if (swiper.animating && params.preventInteractionOnTransition) {
    return;
  }
  if (!swiper.animating && params.cssMode && params.loop) {
    swiper.loopFix();
  }
  let targetEl = e.target;
  if (params.touchEventsTarget === "wrapper") {
    if (!elementIsChildOf(targetEl, swiper.wrapperEl))
      return;
  }
  if ("which" in e && e.which === 3)
    return;
  if ("button" in e && e.button > 0)
    return;
  if (data.isTouched && data.isMoved)
    return;
  const swipingClassHasValue = !!params.noSwipingClass && params.noSwipingClass !== "";
  const eventPath = e.composedPath ? e.composedPath() : e.path;
  if (swipingClassHasValue && e.target && e.target.shadowRoot && eventPath) {
    targetEl = eventPath[0];
  }
  const noSwipingSelector = params.noSwipingSelector ? params.noSwipingSelector : `.${params.noSwipingClass}`;
  const isTargetShadow = !!(e.target && e.target.shadowRoot);
  if (params.noSwiping && (isTargetShadow ? closestElement(noSwipingSelector, targetEl) : targetEl.closest(noSwipingSelector))) {
    swiper.allowClick = true;
    return;
  }
  if (params.swipeHandler) {
    if (!targetEl.closest(params.swipeHandler))
      return;
  }
  touches.currentX = e.pageX;
  touches.currentY = e.pageY;
  const startX = touches.currentX;
  const startY = touches.currentY;
  if (!preventEdgeSwipe(swiper, e, startX)) {
    return;
  }
  Object.assign(data, {
    isTouched: true,
    isMoved: false,
    allowTouchCallbacks: true,
    isScrolling: void 0,
    startMoving: void 0
  });
  touches.startX = startX;
  touches.startY = startY;
  data.touchStartTime = now();
  swiper.allowClick = true;
  swiper.updateSize();
  swiper.swipeDirection = void 0;
  if (params.threshold > 0)
    data.allowThresholdMove = false;
  let preventDefault = true;
  if (targetEl.matches(data.focusableElements)) {
    preventDefault = false;
    if (targetEl.nodeName === "SELECT") {
      data.isTouched = false;
    }
  }
  if (document2.activeElement && document2.activeElement.matches(data.focusableElements) && document2.activeElement !== targetEl && (e.pointerType === "mouse" || e.pointerType !== "mouse" && !targetEl.matches(data.focusableElements))) {
    document2.activeElement.blur();
  }
  const shouldPreventDefault = preventDefault && swiper.allowTouchMove && params.touchStartPreventDefault;
  if ((params.touchStartForcePreventDefault || shouldPreventDefault) && !targetEl.isContentEditable) {
    e.preventDefault();
  }
  if (params.freeMode && params.freeMode.enabled && swiper.freeMode && swiper.animating && !params.cssMode) {
    swiper.freeMode.onTouchStart();
  }
  swiper.emit("touchStart", e);
}
function onTouchMove(event2) {
  const document2 = getDocument();
  const swiper = this;
  const data = swiper.touchEventsData;
  const {
    params,
    touches,
    rtlTranslate: rtl,
    enabled
  } = swiper;
  if (!enabled)
    return;
  if (!params.simulateTouch && event2.pointerType === "mouse")
    return;
  let e = event2;
  if (e.originalEvent)
    e = e.originalEvent;
  if (e.type === "pointermove") {
    if (data.touchId !== null)
      return;
    const id = e.pointerId;
    if (id !== data.pointerId)
      return;
  }
  let targetTouch;
  if (e.type === "touchmove") {
    targetTouch = [...e.changedTouches].filter((t) => t.identifier === data.touchId)[0];
    if (!targetTouch || targetTouch.identifier !== data.touchId)
      return;
  } else {
    targetTouch = e;
  }
  if (!data.isTouched) {
    if (data.startMoving && data.isScrolling) {
      swiper.emit("touchMoveOpposite", e);
    }
    return;
  }
  const pageX = targetTouch.pageX;
  const pageY = targetTouch.pageY;
  if (e.preventedByNestedSwiper) {
    touches.startX = pageX;
    touches.startY = pageY;
    return;
  }
  if (!swiper.allowTouchMove) {
    if (!e.target.matches(data.focusableElements)) {
      swiper.allowClick = false;
    }
    if (data.isTouched) {
      Object.assign(touches, {
        startX: pageX,
        startY: pageY,
        currentX: pageX,
        currentY: pageY
      });
      data.touchStartTime = now();
    }
    return;
  }
  if (params.touchReleaseOnEdges && !params.loop) {
    if (swiper.isVertical()) {
      if (pageY < touches.startY && swiper.translate <= swiper.maxTranslate() || pageY > touches.startY && swiper.translate >= swiper.minTranslate()) {
        data.isTouched = false;
        data.isMoved = false;
        return;
      }
    } else if (pageX < touches.startX && swiper.translate <= swiper.maxTranslate() || pageX > touches.startX && swiper.translate >= swiper.minTranslate()) {
      return;
    }
  }
  if (document2.activeElement && document2.activeElement.matches(data.focusableElements) && document2.activeElement !== e.target && e.pointerType !== "mouse") {
    document2.activeElement.blur();
  }
  if (document2.activeElement) {
    if (e.target === document2.activeElement && e.target.matches(data.focusableElements)) {
      data.isMoved = true;
      swiper.allowClick = false;
      return;
    }
  }
  if (data.allowTouchCallbacks) {
    swiper.emit("touchMove", e);
  }
  touches.previousX = touches.currentX;
  touches.previousY = touches.currentY;
  touches.currentX = pageX;
  touches.currentY = pageY;
  const diffX = touches.currentX - touches.startX;
  const diffY = touches.currentY - touches.startY;
  if (swiper.params.threshold && Math.sqrt(diffX ** 2 + diffY ** 2) < swiper.params.threshold)
    return;
  if (typeof data.isScrolling === "undefined") {
    let touchAngle;
    if (swiper.isHorizontal() && touches.currentY === touches.startY || swiper.isVertical() && touches.currentX === touches.startX) {
      data.isScrolling = false;
    } else {
      if (diffX * diffX + diffY * diffY >= 25) {
        touchAngle = Math.atan2(Math.abs(diffY), Math.abs(diffX)) * 180 / Math.PI;
        data.isScrolling = swiper.isHorizontal() ? touchAngle > params.touchAngle : 90 - touchAngle > params.touchAngle;
      }
    }
  }
  if (data.isScrolling) {
    swiper.emit("touchMoveOpposite", e);
  }
  if (typeof data.startMoving === "undefined") {
    if (touches.currentX !== touches.startX || touches.currentY !== touches.startY) {
      data.startMoving = true;
    }
  }
  if (data.isScrolling || e.type === "touchmove" && data.preventTouchMoveFromPointerMove) {
    data.isTouched = false;
    return;
  }
  if (!data.startMoving) {
    return;
  }
  swiper.allowClick = false;
  if (!params.cssMode && e.cancelable) {
    e.preventDefault();
  }
  if (params.touchMoveStopPropagation && !params.nested) {
    e.stopPropagation();
  }
  let diff = swiper.isHorizontal() ? diffX : diffY;
  let touchesDiff = swiper.isHorizontal() ? touches.currentX - touches.previousX : touches.currentY - touches.previousY;
  if (params.oneWayMovement) {
    diff = Math.abs(diff) * (rtl ? 1 : -1);
    touchesDiff = Math.abs(touchesDiff) * (rtl ? 1 : -1);
  }
  touches.diff = diff;
  diff *= params.touchRatio;
  if (rtl) {
    diff = -diff;
    touchesDiff = -touchesDiff;
  }
  const prevTouchesDirection = swiper.touchesDirection;
  swiper.swipeDirection = diff > 0 ? "prev" : "next";
  swiper.touchesDirection = touchesDiff > 0 ? "prev" : "next";
  const isLoop = swiper.params.loop && !params.cssMode;
  const allowLoopFix = swiper.touchesDirection === "next" && swiper.allowSlideNext || swiper.touchesDirection === "prev" && swiper.allowSlidePrev;
  if (!data.isMoved) {
    if (isLoop && allowLoopFix) {
      swiper.loopFix({
        direction: swiper.swipeDirection
      });
    }
    data.startTranslate = swiper.getTranslate();
    swiper.setTransition(0);
    if (swiper.animating) {
      const evt = new window.CustomEvent("transitionend", {
        bubbles: true,
        cancelable: true,
        detail: {
          bySwiperTouchMove: true
        }
      });
      swiper.wrapperEl.dispatchEvent(evt);
    }
    data.allowMomentumBounce = false;
    if (params.grabCursor && (swiper.allowSlideNext === true || swiper.allowSlidePrev === true)) {
      swiper.setGrabCursor(true);
    }
    swiper.emit("sliderFirstMove", e);
  }
  let loopFixed;
  (/* @__PURE__ */ new Date()).getTime();
  if (data.isMoved && data.allowThresholdMove && prevTouchesDirection !== swiper.touchesDirection && isLoop && allowLoopFix && Math.abs(diff) >= 1) {
    Object.assign(touches, {
      startX: pageX,
      startY: pageY,
      currentX: pageX,
      currentY: pageY,
      startTranslate: data.currentTranslate
    });
    data.loopSwapReset = true;
    data.startTranslate = data.currentTranslate;
    return;
  }
  swiper.emit("sliderMove", e);
  data.isMoved = true;
  data.currentTranslate = diff + data.startTranslate;
  let disableParentSwiper = true;
  let resistanceRatio = params.resistanceRatio;
  if (params.touchReleaseOnEdges) {
    resistanceRatio = 0;
  }
  if (diff > 0) {
    if (isLoop && allowLoopFix && !loopFixed && data.allowThresholdMove && data.currentTranslate > (params.centeredSlides ? swiper.minTranslate() - swiper.slidesSizesGrid[swiper.activeIndex + 1] - (params.slidesPerView !== "auto" && swiper.slides.length - params.slidesPerView >= 2 ? swiper.slidesSizesGrid[swiper.activeIndex + 1] + swiper.params.spaceBetween : 0) - swiper.params.spaceBetween : swiper.minTranslate())) {
      swiper.loopFix({
        direction: "prev",
        setTranslate: true,
        activeSlideIndex: 0
      });
    }
    if (data.currentTranslate > swiper.minTranslate()) {
      disableParentSwiper = false;
      if (params.resistance) {
        data.currentTranslate = swiper.minTranslate() - 1 + (-swiper.minTranslate() + data.startTranslate + diff) ** resistanceRatio;
      }
    }
  } else if (diff < 0) {
    if (isLoop && allowLoopFix && !loopFixed && data.allowThresholdMove && data.currentTranslate < (params.centeredSlides ? swiper.maxTranslate() + swiper.slidesSizesGrid[swiper.slidesSizesGrid.length - 1] + swiper.params.spaceBetween + (params.slidesPerView !== "auto" && swiper.slides.length - params.slidesPerView >= 2 ? swiper.slidesSizesGrid[swiper.slidesSizesGrid.length - 1] + swiper.params.spaceBetween : 0) : swiper.maxTranslate())) {
      swiper.loopFix({
        direction: "next",
        setTranslate: true,
        activeSlideIndex: swiper.slides.length - (params.slidesPerView === "auto" ? swiper.slidesPerViewDynamic() : Math.ceil(parseFloat(params.slidesPerView, 10)))
      });
    }
    if (data.currentTranslate < swiper.maxTranslate()) {
      disableParentSwiper = false;
      if (params.resistance) {
        data.currentTranslate = swiper.maxTranslate() + 1 - (swiper.maxTranslate() - data.startTranslate - diff) ** resistanceRatio;
      }
    }
  }
  if (disableParentSwiper) {
    e.preventedByNestedSwiper = true;
  }
  if (!swiper.allowSlideNext && swiper.swipeDirection === "next" && data.currentTranslate < data.startTranslate) {
    data.currentTranslate = data.startTranslate;
  }
  if (!swiper.allowSlidePrev && swiper.swipeDirection === "prev" && data.currentTranslate > data.startTranslate) {
    data.currentTranslate = data.startTranslate;
  }
  if (!swiper.allowSlidePrev && !swiper.allowSlideNext) {
    data.currentTranslate = data.startTranslate;
  }
  if (params.threshold > 0) {
    if (Math.abs(diff) > params.threshold || data.allowThresholdMove) {
      if (!data.allowThresholdMove) {
        data.allowThresholdMove = true;
        touches.startX = touches.currentX;
        touches.startY = touches.currentY;
        data.currentTranslate = data.startTranslate;
        touches.diff = swiper.isHorizontal() ? touches.currentX - touches.startX : touches.currentY - touches.startY;
        return;
      }
    } else {
      data.currentTranslate = data.startTranslate;
      return;
    }
  }
  if (!params.followFinger || params.cssMode)
    return;
  if (params.freeMode && params.freeMode.enabled && swiper.freeMode || params.watchSlidesProgress) {
    swiper.updateActiveIndex();
    swiper.updateSlidesClasses();
  }
  if (params.freeMode && params.freeMode.enabled && swiper.freeMode) {
    swiper.freeMode.onTouchMove();
  }
  swiper.updateProgress(data.currentTranslate);
  swiper.setTranslate(data.currentTranslate);
}
function onTouchEnd(event2) {
  const swiper = this;
  const data = swiper.touchEventsData;
  let e = event2;
  if (e.originalEvent)
    e = e.originalEvent;
  let targetTouch;
  const isTouchEvent = e.type === "touchend" || e.type === "touchcancel";
  if (!isTouchEvent) {
    if (data.touchId !== null)
      return;
    if (e.pointerId !== data.pointerId)
      return;
    targetTouch = e;
  } else {
    targetTouch = [...e.changedTouches].filter((t) => t.identifier === data.touchId)[0];
    if (!targetTouch || targetTouch.identifier !== data.touchId)
      return;
  }
  if (["pointercancel", "pointerout", "pointerleave", "contextmenu"].includes(e.type)) {
    const proceed = ["pointercancel", "contextmenu"].includes(e.type) && (swiper.browser.isSafari || swiper.browser.isWebView);
    if (!proceed) {
      return;
    }
  }
  data.pointerId = null;
  data.touchId = null;
  const {
    params,
    touches,
    rtlTranslate: rtl,
    slidesGrid,
    enabled
  } = swiper;
  if (!enabled)
    return;
  if (!params.simulateTouch && e.pointerType === "mouse")
    return;
  if (data.allowTouchCallbacks) {
    swiper.emit("touchEnd", e);
  }
  data.allowTouchCallbacks = false;
  if (!data.isTouched) {
    if (data.isMoved && params.grabCursor) {
      swiper.setGrabCursor(false);
    }
    data.isMoved = false;
    data.startMoving = false;
    return;
  }
  if (params.grabCursor && data.isMoved && data.isTouched && (swiper.allowSlideNext === true || swiper.allowSlidePrev === true)) {
    swiper.setGrabCursor(false);
  }
  const touchEndTime = now();
  const timeDiff = touchEndTime - data.touchStartTime;
  if (swiper.allowClick) {
    const pathTree = e.path || e.composedPath && e.composedPath();
    swiper.updateClickedSlide(pathTree && pathTree[0] || e.target, pathTree);
    swiper.emit("tap click", e);
    if (timeDiff < 300 && touchEndTime - data.lastClickTime < 300) {
      swiper.emit("doubleTap doubleClick", e);
    }
  }
  data.lastClickTime = now();
  nextTick(() => {
    if (!swiper.destroyed)
      swiper.allowClick = true;
  });
  if (!data.isTouched || !data.isMoved || !swiper.swipeDirection || touches.diff === 0 && !data.loopSwapReset || data.currentTranslate === data.startTranslate && !data.loopSwapReset) {
    data.isTouched = false;
    data.isMoved = false;
    data.startMoving = false;
    return;
  }
  data.isTouched = false;
  data.isMoved = false;
  data.startMoving = false;
  let currentPos;
  if (params.followFinger) {
    currentPos = rtl ? swiper.translate : -swiper.translate;
  } else {
    currentPos = -data.currentTranslate;
  }
  if (params.cssMode) {
    return;
  }
  if (params.freeMode && params.freeMode.enabled) {
    swiper.freeMode.onTouchEnd({
      currentPos
    });
    return;
  }
  const swipeToLast = currentPos >= -swiper.maxTranslate() && !swiper.params.loop;
  let stopIndex = 0;
  let groupSize = swiper.slidesSizesGrid[0];
  for (let i = 0; i < slidesGrid.length; i += i < params.slidesPerGroupSkip ? 1 : params.slidesPerGroup) {
    const increment2 = i < params.slidesPerGroupSkip - 1 ? 1 : params.slidesPerGroup;
    if (typeof slidesGrid[i + increment2] !== "undefined") {
      if (swipeToLast || currentPos >= slidesGrid[i] && currentPos < slidesGrid[i + increment2]) {
        stopIndex = i;
        groupSize = slidesGrid[i + increment2] - slidesGrid[i];
      }
    } else if (swipeToLast || currentPos >= slidesGrid[i]) {
      stopIndex = i;
      groupSize = slidesGrid[slidesGrid.length - 1] - slidesGrid[slidesGrid.length - 2];
    }
  }
  let rewindFirstIndex = null;
  let rewindLastIndex = null;
  if (params.rewind) {
    if (swiper.isBeginning) {
      rewindLastIndex = params.virtual && params.virtual.enabled && swiper.virtual ? swiper.virtual.slides.length - 1 : swiper.slides.length - 1;
    } else if (swiper.isEnd) {
      rewindFirstIndex = 0;
    }
  }
  const ratio = (currentPos - slidesGrid[stopIndex]) / groupSize;
  const increment = stopIndex < params.slidesPerGroupSkip - 1 ? 1 : params.slidesPerGroup;
  if (timeDiff > params.longSwipesMs) {
    if (!params.longSwipes) {
      swiper.slideTo(swiper.activeIndex);
      return;
    }
    if (swiper.swipeDirection === "next") {
      if (ratio >= params.longSwipesRatio)
        swiper.slideTo(params.rewind && swiper.isEnd ? rewindFirstIndex : stopIndex + increment);
      else
        swiper.slideTo(stopIndex);
    }
    if (swiper.swipeDirection === "prev") {
      if (ratio > 1 - params.longSwipesRatio) {
        swiper.slideTo(stopIndex + increment);
      } else if (rewindLastIndex !== null && ratio < 0 && Math.abs(ratio) > params.longSwipesRatio) {
        swiper.slideTo(rewindLastIndex);
      } else {
        swiper.slideTo(stopIndex);
      }
    }
  } else {
    if (!params.shortSwipes) {
      swiper.slideTo(swiper.activeIndex);
      return;
    }
    const isNavButtonTarget = swiper.navigation && (e.target === swiper.navigation.nextEl || e.target === swiper.navigation.prevEl);
    if (!isNavButtonTarget) {
      if (swiper.swipeDirection === "next") {
        swiper.slideTo(rewindFirstIndex !== null ? rewindFirstIndex : stopIndex + increment);
      }
      if (swiper.swipeDirection === "prev") {
        swiper.slideTo(rewindLastIndex !== null ? rewindLastIndex : stopIndex);
      }
    } else if (e.target === swiper.navigation.nextEl) {
      swiper.slideTo(stopIndex + increment);
    } else {
      swiper.slideTo(stopIndex);
    }
  }
}
function onResize() {
  const swiper = this;
  const {
    params,
    el
  } = swiper;
  if (el && el.offsetWidth === 0)
    return;
  if (params.breakpoints) {
    swiper.setBreakpoint();
  }
  const {
    allowSlideNext,
    allowSlidePrev,
    snapGrid
  } = swiper;
  const isVirtual = swiper.virtual && swiper.params.virtual.enabled;
  swiper.allowSlideNext = true;
  swiper.allowSlidePrev = true;
  swiper.updateSize();
  swiper.updateSlides();
  swiper.updateSlidesClasses();
  const isVirtualLoop = isVirtual && params.loop;
  if ((params.slidesPerView === "auto" || params.slidesPerView > 1) && swiper.isEnd && !swiper.isBeginning && !swiper.params.centeredSlides && !isVirtualLoop) {
    swiper.slideTo(swiper.slides.length - 1, 0, false, true);
  } else {
    if (swiper.params.loop && !isVirtual) {
      swiper.slideToLoop(swiper.realIndex, 0, false, true);
    } else {
      swiper.slideTo(swiper.activeIndex, 0, false, true);
    }
  }
  if (swiper.autoplay && swiper.autoplay.running && swiper.autoplay.paused) {
    clearTimeout(swiper.autoplay.resizeTimeout);
    swiper.autoplay.resizeTimeout = setTimeout(() => {
      if (swiper.autoplay && swiper.autoplay.running && swiper.autoplay.paused) {
        swiper.autoplay.resume();
      }
    }, 500);
  }
  swiper.allowSlidePrev = allowSlidePrev;
  swiper.allowSlideNext = allowSlideNext;
  if (swiper.params.watchOverflow && snapGrid !== swiper.snapGrid) {
    swiper.checkOverflow();
  }
}
function onClick(e) {
  const swiper = this;
  if (!swiper.enabled)
    return;
  if (!swiper.allowClick) {
    if (swiper.params.preventClicks)
      e.preventDefault();
    if (swiper.params.preventClicksPropagation && swiper.animating) {
      e.stopPropagation();
      e.stopImmediatePropagation();
    }
  }
}
function onScroll() {
  const swiper = this;
  const {
    wrapperEl,
    rtlTranslate,
    enabled
  } = swiper;
  if (!enabled)
    return;
  swiper.previousTranslate = swiper.translate;
  if (swiper.isHorizontal()) {
    swiper.translate = -wrapperEl.scrollLeft;
  } else {
    swiper.translate = -wrapperEl.scrollTop;
  }
  if (swiper.translate === 0)
    swiper.translate = 0;
  swiper.updateActiveIndex();
  swiper.updateSlidesClasses();
  let newProgress;
  const translatesDiff = swiper.maxTranslate() - swiper.minTranslate();
  if (translatesDiff === 0) {
    newProgress = 0;
  } else {
    newProgress = (swiper.translate - swiper.minTranslate()) / translatesDiff;
  }
  if (newProgress !== swiper.progress) {
    swiper.updateProgress(rtlTranslate ? -swiper.translate : swiper.translate);
  }
  swiper.emit("setTranslate", swiper.translate, false);
}
function onLoad(e) {
  const swiper = this;
  processLazyPreloader(swiper, e.target);
  if (swiper.params.cssMode || swiper.params.slidesPerView !== "auto" && !swiper.params.autoHeight) {
    return;
  }
  swiper.update();
}
function onDocumentTouchStart() {
  const swiper = this;
  if (swiper.documentTouchHandlerProceeded)
    return;
  swiper.documentTouchHandlerProceeded = true;
  if (swiper.params.touchReleaseOnEdges) {
    swiper.el.style.touchAction = "auto";
  }
}
var events = (swiper, method) => {
  const document2 = getDocument();
  const {
    params,
    el,
    wrapperEl,
    device
  } = swiper;
  const capture = !!params.nested;
  const domMethod = method === "on" ? "addEventListener" : "removeEventListener";
  const swiperMethod = method;
  if (!el || typeof el === "string")
    return;
  document2[domMethod]("touchstart", swiper.onDocumentTouchStart, {
    passive: false,
    capture
  });
  el[domMethod]("touchstart", swiper.onTouchStart, {
    passive: false
  });
  el[domMethod]("pointerdown", swiper.onTouchStart, {
    passive: false
  });
  document2[domMethod]("touchmove", swiper.onTouchMove, {
    passive: false,
    capture
  });
  document2[domMethod]("pointermove", swiper.onTouchMove, {
    passive: false,
    capture
  });
  document2[domMethod]("touchend", swiper.onTouchEnd, {
    passive: true
  });
  document2[domMethod]("pointerup", swiper.onTouchEnd, {
    passive: true
  });
  document2[domMethod]("pointercancel", swiper.onTouchEnd, {
    passive: true
  });
  document2[domMethod]("touchcancel", swiper.onTouchEnd, {
    passive: true
  });
  document2[domMethod]("pointerout", swiper.onTouchEnd, {
    passive: true
  });
  document2[domMethod]("pointerleave", swiper.onTouchEnd, {
    passive: true
  });
  document2[domMethod]("contextmenu", swiper.onTouchEnd, {
    passive: true
  });
  if (params.preventClicks || params.preventClicksPropagation) {
    el[domMethod]("click", swiper.onClick, true);
  }
  if (params.cssMode) {
    wrapperEl[domMethod]("scroll", swiper.onScroll);
  }
  if (params.updateOnWindowResize) {
    swiper[swiperMethod](device.ios || device.android ? "resize orientationchange observerUpdate" : "resize observerUpdate", onResize, true);
  } else {
    swiper[swiperMethod]("observerUpdate", onResize, true);
  }
  el[domMethod]("load", swiper.onLoad, {
    capture: true
  });
};
function attachEvents() {
  const swiper = this;
  const {
    params
  } = swiper;
  swiper.onTouchStart = onTouchStart.bind(swiper);
  swiper.onTouchMove = onTouchMove.bind(swiper);
  swiper.onTouchEnd = onTouchEnd.bind(swiper);
  swiper.onDocumentTouchStart = onDocumentTouchStart.bind(swiper);
  if (params.cssMode) {
    swiper.onScroll = onScroll.bind(swiper);
  }
  swiper.onClick = onClick.bind(swiper);
  swiper.onLoad = onLoad.bind(swiper);
  events(swiper, "on");
}
function detachEvents() {
  const swiper = this;
  events(swiper, "off");
}
var events$1 = {
  attachEvents,
  detachEvents
};
var isGridEnabled = (swiper, params) => {
  return swiper.grid && params.grid && params.grid.rows > 1;
};
function setBreakpoint() {
  const swiper = this;
  const {
    realIndex,
    initialized,
    params,
    el
  } = swiper;
  const breakpoints2 = params.breakpoints;
  if (!breakpoints2 || breakpoints2 && Object.keys(breakpoints2).length === 0)
    return;
  const breakpoint = swiper.getBreakpoint(breakpoints2, swiper.params.breakpointsBase, swiper.el);
  if (!breakpoint || swiper.currentBreakpoint === breakpoint)
    return;
  const breakpointOnlyParams = breakpoint in breakpoints2 ? breakpoints2[breakpoint] : void 0;
  const breakpointParams = breakpointOnlyParams || swiper.originalParams;
  const wasMultiRow = isGridEnabled(swiper, params);
  const isMultiRow = isGridEnabled(swiper, breakpointParams);
  const wasGrabCursor = swiper.params.grabCursor;
  const isGrabCursor = breakpointParams.grabCursor;
  const wasEnabled = params.enabled;
  if (wasMultiRow && !isMultiRow) {
    el.classList.remove(`${params.containerModifierClass}grid`, `${params.containerModifierClass}grid-column`);
    swiper.emitContainerClasses();
  } else if (!wasMultiRow && isMultiRow) {
    el.classList.add(`${params.containerModifierClass}grid`);
    if (breakpointParams.grid.fill && breakpointParams.grid.fill === "column" || !breakpointParams.grid.fill && params.grid.fill === "column") {
      el.classList.add(`${params.containerModifierClass}grid-column`);
    }
    swiper.emitContainerClasses();
  }
  if (wasGrabCursor && !isGrabCursor) {
    swiper.unsetGrabCursor();
  } else if (!wasGrabCursor && isGrabCursor) {
    swiper.setGrabCursor();
  }
  ["navigation", "pagination", "scrollbar"].forEach((prop) => {
    if (typeof breakpointParams[prop] === "undefined")
      return;
    const wasModuleEnabled = params[prop] && params[prop].enabled;
    const isModuleEnabled = breakpointParams[prop] && breakpointParams[prop].enabled;
    if (wasModuleEnabled && !isModuleEnabled) {
      swiper[prop].disable();
    }
    if (!wasModuleEnabled && isModuleEnabled) {
      swiper[prop].enable();
    }
  });
  const directionChanged = breakpointParams.direction && breakpointParams.direction !== params.direction;
  const needsReLoop = params.loop && (breakpointParams.slidesPerView !== params.slidesPerView || directionChanged);
  const wasLoop = params.loop;
  if (directionChanged && initialized) {
    swiper.changeDirection();
  }
  extend2(swiper.params, breakpointParams);
  const isEnabled = swiper.params.enabled;
  const hasLoop = swiper.params.loop;
  Object.assign(swiper, {
    allowTouchMove: swiper.params.allowTouchMove,
    allowSlideNext: swiper.params.allowSlideNext,
    allowSlidePrev: swiper.params.allowSlidePrev
  });
  if (wasEnabled && !isEnabled) {
    swiper.disable();
  } else if (!wasEnabled && isEnabled) {
    swiper.enable();
  }
  swiper.currentBreakpoint = breakpoint;
  swiper.emit("_beforeBreakpoint", breakpointParams);
  if (initialized) {
    if (needsReLoop) {
      swiper.loopDestroy();
      swiper.loopCreate(realIndex);
      swiper.updateSlides();
    } else if (!wasLoop && hasLoop) {
      swiper.loopCreate(realIndex);
      swiper.updateSlides();
    } else if (wasLoop && !hasLoop) {
      swiper.loopDestroy();
    }
  }
  swiper.emit("breakpoint", breakpointParams);
}
function getBreakpoint(breakpoints2, base, containerEl) {
  if (base === void 0) {
    base = "window";
  }
  if (!breakpoints2 || base === "container" && !containerEl)
    return void 0;
  let breakpoint = false;
  const window2 = getWindow();
  const currentHeight = base === "window" ? window2.innerHeight : containerEl.clientHeight;
  const points = Object.keys(breakpoints2).map((point) => {
    if (typeof point === "string" && point.indexOf("@") === 0) {
      const minRatio = parseFloat(point.substr(1));
      const value = currentHeight * minRatio;
      return {
        value,
        point
      };
    }
    return {
      value: point,
      point
    };
  });
  points.sort((a, b) => parseInt(a.value, 10) - parseInt(b.value, 10));
  for (let i = 0; i < points.length; i += 1) {
    const {
      point,
      value
    } = points[i];
    if (base === "window") {
      if (window2.matchMedia(`(min-width: ${value}px)`).matches) {
        breakpoint = point;
      }
    } else if (value <= containerEl.clientWidth) {
      breakpoint = point;
    }
  }
  return breakpoint || "max";
}
var breakpoints = {
  setBreakpoint,
  getBreakpoint
};
function prepareClasses(entries, prefix) {
  const resultClasses = [];
  entries.forEach((item) => {
    if (typeof item === "object") {
      Object.keys(item).forEach((classNames) => {
        if (item[classNames]) {
          resultClasses.push(prefix + classNames);
        }
      });
    } else if (typeof item === "string") {
      resultClasses.push(prefix + item);
    }
  });
  return resultClasses;
}
function addClasses() {
  const swiper = this;
  const {
    classNames,
    params,
    rtl,
    el,
    device
  } = swiper;
  const suffixes = prepareClasses(["initialized", params.direction, {
    "free-mode": swiper.params.freeMode && params.freeMode.enabled
  }, {
    "autoheight": params.autoHeight
  }, {
    "rtl": rtl
  }, {
    "grid": params.grid && params.grid.rows > 1
  }, {
    "grid-column": params.grid && params.grid.rows > 1 && params.grid.fill === "column"
  }, {
    "android": device.android
  }, {
    "ios": device.ios
  }, {
    "css-mode": params.cssMode
  }, {
    "centered": params.cssMode && params.centeredSlides
  }, {
    "watch-progress": params.watchSlidesProgress
  }], params.containerModifierClass);
  classNames.push(...suffixes);
  el.classList.add(...classNames);
  swiper.emitContainerClasses();
}
function removeClasses() {
  const swiper = this;
  const {
    el,
    classNames
  } = swiper;
  if (!el || typeof el === "string")
    return;
  el.classList.remove(...classNames);
  swiper.emitContainerClasses();
}
var classes = {
  addClasses,
  removeClasses
};
function checkOverflow() {
  const swiper = this;
  const {
    isLocked: wasLocked,
    params
  } = swiper;
  const {
    slidesOffsetBefore
  } = params;
  if (slidesOffsetBefore) {
    const lastSlideIndex = swiper.slides.length - 1;
    const lastSlideRightEdge = swiper.slidesGrid[lastSlideIndex] + swiper.slidesSizesGrid[lastSlideIndex] + slidesOffsetBefore * 2;
    swiper.isLocked = swiper.size > lastSlideRightEdge;
  } else {
    swiper.isLocked = swiper.snapGrid.length === 1;
  }
  if (params.allowSlideNext === true) {
    swiper.allowSlideNext = !swiper.isLocked;
  }
  if (params.allowSlidePrev === true) {
    swiper.allowSlidePrev = !swiper.isLocked;
  }
  if (wasLocked && wasLocked !== swiper.isLocked) {
    swiper.isEnd = false;
  }
  if (wasLocked !== swiper.isLocked) {
    swiper.emit(swiper.isLocked ? "lock" : "unlock");
  }
}
var checkOverflow$1 = {
  checkOverflow
};
var defaults = {
  init: true,
  direction: "horizontal",
  oneWayMovement: false,
  swiperElementNodeName: "SWIPER-CONTAINER",
  touchEventsTarget: "wrapper",
  initialSlide: 0,
  speed: 300,
  cssMode: false,
  updateOnWindowResize: true,
  resizeObserver: true,
  nested: false,
  createElements: false,
  eventsPrefix: "swiper",
  enabled: true,
  focusableElements: "input, select, option, textarea, button, video, label",
  // Overrides
  width: null,
  height: null,
  //
  preventInteractionOnTransition: false,
  // ssr
  userAgent: null,
  url: null,
  // To support iOS's swipe-to-go-back gesture (when being used in-app).
  edgeSwipeDetection: false,
  edgeSwipeThreshold: 20,
  // Autoheight
  autoHeight: false,
  // Set wrapper width
  setWrapperSize: false,
  // Virtual Translate
  virtualTranslate: false,
  // Effects
  effect: "slide",
  // 'slide' or 'fade' or 'cube' or 'coverflow' or 'flip'
  // Breakpoints
  breakpoints: void 0,
  breakpointsBase: "window",
  // Slides grid
  spaceBetween: 0,
  slidesPerView: 1,
  slidesPerGroup: 1,
  slidesPerGroupSkip: 0,
  slidesPerGroupAuto: false,
  centeredSlides: false,
  centeredSlidesBounds: false,
  slidesOffsetBefore: 0,
  // in px
  slidesOffsetAfter: 0,
  // in px
  normalizeSlideIndex: true,
  centerInsufficientSlides: false,
  // Disable swiper and hide navigation when container not overflow
  watchOverflow: true,
  // Round length
  roundLengths: false,
  // Touches
  touchRatio: 1,
  touchAngle: 45,
  simulateTouch: true,
  shortSwipes: true,
  longSwipes: true,
  longSwipesRatio: 0.5,
  longSwipesMs: 300,
  followFinger: true,
  allowTouchMove: true,
  threshold: 5,
  touchMoveStopPropagation: false,
  touchStartPreventDefault: true,
  touchStartForcePreventDefault: false,
  touchReleaseOnEdges: false,
  // Unique Navigation Elements
  uniqueNavElements: true,
  // Resistance
  resistance: true,
  resistanceRatio: 0.85,
  // Progress
  watchSlidesProgress: false,
  // Cursor
  grabCursor: false,
  // Clicks
  preventClicks: true,
  preventClicksPropagation: true,
  slideToClickedSlide: false,
  // loop
  loop: false,
  loopAddBlankSlides: true,
  loopAdditionalSlides: 0,
  loopPreventsSliding: true,
  // rewind
  rewind: false,
  // Swiping/no swiping
  allowSlidePrev: true,
  allowSlideNext: true,
  swipeHandler: null,
  // '.swipe-handler',
  noSwiping: true,
  noSwipingClass: "swiper-no-swiping",
  noSwipingSelector: null,
  // Passive Listeners
  passiveListeners: true,
  maxBackfaceHiddenSlides: 10,
  // NS
  containerModifierClass: "swiper-",
  // NEW
  slideClass: "swiper-slide",
  slideBlankClass: "swiper-slide-blank",
  slideActiveClass: "swiper-slide-active",
  slideVisibleClass: "swiper-slide-visible",
  slideFullyVisibleClass: "swiper-slide-fully-visible",
  slideNextClass: "swiper-slide-next",
  slidePrevClass: "swiper-slide-prev",
  wrapperClass: "swiper-wrapper",
  lazyPreloaderClass: "swiper-lazy-preloader",
  lazyPreloadPrevNext: 0,
  // Callbacks
  runCallbacksOnInit: true,
  // Internals
  _emitClasses: false
};
function moduleExtendParams(params, allModulesParams) {
  return function extendParams(obj) {
    if (obj === void 0) {
      obj = {};
    }
    const moduleParamName = Object.keys(obj)[0];
    const moduleParams = obj[moduleParamName];
    if (typeof moduleParams !== "object" || moduleParams === null) {
      extend2(allModulesParams, obj);
      return;
    }
    if (params[moduleParamName] === true) {
      params[moduleParamName] = {
        enabled: true
      };
    }
    if (moduleParamName === "navigation" && params[moduleParamName] && params[moduleParamName].enabled && !params[moduleParamName].prevEl && !params[moduleParamName].nextEl) {
      params[moduleParamName].auto = true;
    }
    if (["pagination", "scrollbar"].indexOf(moduleParamName) >= 0 && params[moduleParamName] && params[moduleParamName].enabled && !params[moduleParamName].el) {
      params[moduleParamName].auto = true;
    }
    if (!(moduleParamName in params && "enabled" in moduleParams)) {
      extend2(allModulesParams, obj);
      return;
    }
    if (typeof params[moduleParamName] === "object" && !("enabled" in params[moduleParamName])) {
      params[moduleParamName].enabled = true;
    }
    if (!params[moduleParamName])
      params[moduleParamName] = {
        enabled: false
      };
    extend2(allModulesParams, obj);
  };
}
var prototypes = {
  eventsEmitter,
  update,
  translate,
  transition,
  slide,
  loop,
  grabCursor,
  events: events$1,
  breakpoints,
  checkOverflow: checkOverflow$1,
  classes
};
var extendedDefaults = {};
var Swiper = class _Swiper {
  constructor() {
    let el;
    let params;
    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }
    if (args.length === 1 && args[0].constructor && Object.prototype.toString.call(args[0]).slice(8, -1) === "Object") {
      params = args[0];
    } else {
      [el, params] = args;
    }
    if (!params)
      params = {};
    params = extend2({}, params);
    if (el && !params.el)
      params.el = el;
    const document2 = getDocument();
    if (params.el && typeof params.el === "string" && document2.querySelectorAll(params.el).length > 1) {
      const swipers = [];
      document2.querySelectorAll(params.el).forEach((containerEl) => {
        const newParams = extend2({}, params, {
          el: containerEl
        });
        swipers.push(new _Swiper(newParams));
      });
      return swipers;
    }
    const swiper = this;
    swiper.__swiper__ = true;
    swiper.support = getSupport();
    swiper.device = getDevice({
      userAgent: params.userAgent
    });
    swiper.browser = getBrowser();
    swiper.eventsListeners = {};
    swiper.eventsAnyListeners = [];
    swiper.modules = [...swiper.__modules__];
    if (params.modules && Array.isArray(params.modules)) {
      swiper.modules.push(...params.modules);
    }
    const allModulesParams = {};
    swiper.modules.forEach((mod) => {
      mod({
        params,
        swiper,
        extendParams: moduleExtendParams(params, allModulesParams),
        on: swiper.on.bind(swiper),
        once: swiper.once.bind(swiper),
        off: swiper.off.bind(swiper),
        emit: swiper.emit.bind(swiper)
      });
    });
    const swiperParams = extend2({}, defaults, allModulesParams);
    swiper.params = extend2({}, swiperParams, extendedDefaults, params);
    swiper.originalParams = extend2({}, swiper.params);
    swiper.passedParams = extend2({}, params);
    if (swiper.params && swiper.params.on) {
      Object.keys(swiper.params.on).forEach((eventName) => {
        swiper.on(eventName, swiper.params.on[eventName]);
      });
    }
    if (swiper.params && swiper.params.onAny) {
      swiper.onAny(swiper.params.onAny);
    }
    Object.assign(swiper, {
      enabled: swiper.params.enabled,
      el,
      // Classes
      classNames: [],
      // Slides
      slides: [],
      slidesGrid: [],
      snapGrid: [],
      slidesSizesGrid: [],
      // isDirection
      isHorizontal() {
        return swiper.params.direction === "horizontal";
      },
      isVertical() {
        return swiper.params.direction === "vertical";
      },
      // Indexes
      activeIndex: 0,
      realIndex: 0,
      //
      isBeginning: true,
      isEnd: false,
      // Props
      translate: 0,
      previousTranslate: 0,
      progress: 0,
      velocity: 0,
      animating: false,
      cssOverflowAdjustment() {
        return Math.trunc(this.translate / 2 ** 23) * 2 ** 23;
      },
      // Locks
      allowSlideNext: swiper.params.allowSlideNext,
      allowSlidePrev: swiper.params.allowSlidePrev,
      // Touch Events
      touchEventsData: {
        isTouched: void 0,
        isMoved: void 0,
        allowTouchCallbacks: void 0,
        touchStartTime: void 0,
        isScrolling: void 0,
        currentTranslate: void 0,
        startTranslate: void 0,
        allowThresholdMove: void 0,
        // Form elements to match
        focusableElements: swiper.params.focusableElements,
        // Last click time
        lastClickTime: 0,
        clickTimeout: void 0,
        // Velocities
        velocities: [],
        allowMomentumBounce: void 0,
        startMoving: void 0,
        pointerId: null,
        touchId: null
      },
      // Clicks
      allowClick: true,
      // Touches
      allowTouchMove: swiper.params.allowTouchMove,
      touches: {
        startX: 0,
        startY: 0,
        currentX: 0,
        currentY: 0,
        diff: 0
      },
      // Images
      imagesToLoad: [],
      imagesLoaded: 0
    });
    swiper.emit("_swiper");
    if (swiper.params.init) {
      swiper.init();
    }
    return swiper;
  }
  getDirectionLabel(property) {
    if (this.isHorizontal()) {
      return property;
    }
    return {
      "width": "height",
      "margin-top": "margin-left",
      "margin-bottom ": "margin-right",
      "margin-left": "margin-top",
      "margin-right": "margin-bottom",
      "padding-left": "padding-top",
      "padding-right": "padding-bottom",
      "marginRight": "marginBottom"
    }[property];
  }
  getSlideIndex(slideEl) {
    const {
      slidesEl,
      params
    } = this;
    const slides = elementChildren(slidesEl, `.${params.slideClass}, swiper-slide`);
    const firstSlideIndex = elementIndex(slides[0]);
    return elementIndex(slideEl) - firstSlideIndex;
  }
  getSlideIndexByData(index) {
    return this.getSlideIndex(this.slides.filter((slideEl) => slideEl.getAttribute("data-swiper-slide-index") * 1 === index)[0]);
  }
  recalcSlides() {
    const swiper = this;
    const {
      slidesEl,
      params
    } = swiper;
    swiper.slides = elementChildren(slidesEl, `.${params.slideClass}, swiper-slide`);
  }
  enable() {
    const swiper = this;
    if (swiper.enabled)
      return;
    swiper.enabled = true;
    if (swiper.params.grabCursor) {
      swiper.setGrabCursor();
    }
    swiper.emit("enable");
  }
  disable() {
    const swiper = this;
    if (!swiper.enabled)
      return;
    swiper.enabled = false;
    if (swiper.params.grabCursor) {
      swiper.unsetGrabCursor();
    }
    swiper.emit("disable");
  }
  setProgress(progress, speed) {
    const swiper = this;
    progress = Math.min(Math.max(progress, 0), 1);
    const min = swiper.minTranslate();
    const max = swiper.maxTranslate();
    const current = (max - min) * progress + min;
    swiper.translateTo(current, typeof speed === "undefined" ? 0 : speed);
    swiper.updateActiveIndex();
    swiper.updateSlidesClasses();
  }
  emitContainerClasses() {
    const swiper = this;
    if (!swiper.params._emitClasses || !swiper.el)
      return;
    const cls = swiper.el.className.split(" ").filter((className) => {
      return className.indexOf("swiper") === 0 || className.indexOf(swiper.params.containerModifierClass) === 0;
    });
    swiper.emit("_containerClasses", cls.join(" "));
  }
  getSlideClasses(slideEl) {
    const swiper = this;
    if (swiper.destroyed)
      return "";
    return slideEl.className.split(" ").filter((className) => {
      return className.indexOf("swiper-slide") === 0 || className.indexOf(swiper.params.slideClass) === 0;
    }).join(" ");
  }
  emitSlidesClasses() {
    const swiper = this;
    if (!swiper.params._emitClasses || !swiper.el)
      return;
    const updates = [];
    swiper.slides.forEach((slideEl) => {
      const classNames = swiper.getSlideClasses(slideEl);
      updates.push({
        slideEl,
        classNames
      });
      swiper.emit("_slideClass", slideEl, classNames);
    });
    swiper.emit("_slideClasses", updates);
  }
  slidesPerViewDynamic(view, exact) {
    if (view === void 0) {
      view = "current";
    }
    if (exact === void 0) {
      exact = false;
    }
    const swiper = this;
    const {
      params,
      slides,
      slidesGrid,
      slidesSizesGrid,
      size: swiperSize,
      activeIndex
    } = swiper;
    let spv = 1;
    if (typeof params.slidesPerView === "number")
      return params.slidesPerView;
    if (params.centeredSlides) {
      let slideSize = slides[activeIndex] ? Math.ceil(slides[activeIndex].swiperSlideSize) : 0;
      let breakLoop;
      for (let i = activeIndex + 1; i < slides.length; i += 1) {
        if (slides[i] && !breakLoop) {
          slideSize += Math.ceil(slides[i].swiperSlideSize);
          spv += 1;
          if (slideSize > swiperSize)
            breakLoop = true;
        }
      }
      for (let i = activeIndex - 1; i >= 0; i -= 1) {
        if (slides[i] && !breakLoop) {
          slideSize += slides[i].swiperSlideSize;
          spv += 1;
          if (slideSize > swiperSize)
            breakLoop = true;
        }
      }
    } else {
      if (view === "current") {
        for (let i = activeIndex + 1; i < slides.length; i += 1) {
          const slideInView = exact ? slidesGrid[i] + slidesSizesGrid[i] - slidesGrid[activeIndex] < swiperSize : slidesGrid[i] - slidesGrid[activeIndex] < swiperSize;
          if (slideInView) {
            spv += 1;
          }
        }
      } else {
        for (let i = activeIndex - 1; i >= 0; i -= 1) {
          const slideInView = slidesGrid[activeIndex] - slidesGrid[i] < swiperSize;
          if (slideInView) {
            spv += 1;
          }
        }
      }
    }
    return spv;
  }
  update() {
    const swiper = this;
    if (!swiper || swiper.destroyed)
      return;
    const {
      snapGrid,
      params
    } = swiper;
    if (params.breakpoints) {
      swiper.setBreakpoint();
    }
    [...swiper.el.querySelectorAll('[loading="lazy"]')].forEach((imageEl) => {
      if (imageEl.complete) {
        processLazyPreloader(swiper, imageEl);
      }
    });
    swiper.updateSize();
    swiper.updateSlides();
    swiper.updateProgress();
    swiper.updateSlidesClasses();
    function setTranslate2() {
      const translateValue = swiper.rtlTranslate ? swiper.translate * -1 : swiper.translate;
      const newTranslate = Math.min(Math.max(translateValue, swiper.maxTranslate()), swiper.minTranslate());
      swiper.setTranslate(newTranslate);
      swiper.updateActiveIndex();
      swiper.updateSlidesClasses();
    }
    let translated;
    if (params.freeMode && params.freeMode.enabled && !params.cssMode) {
      setTranslate2();
      if (params.autoHeight) {
        swiper.updateAutoHeight();
      }
    } else {
      if ((params.slidesPerView === "auto" || params.slidesPerView > 1) && swiper.isEnd && !params.centeredSlides) {
        const slides = swiper.virtual && params.virtual.enabled ? swiper.virtual.slides : swiper.slides;
        translated = swiper.slideTo(slides.length - 1, 0, false, true);
      } else {
        translated = swiper.slideTo(swiper.activeIndex, 0, false, true);
      }
      if (!translated) {
        setTranslate2();
      }
    }
    if (params.watchOverflow && snapGrid !== swiper.snapGrid) {
      swiper.checkOverflow();
    }
    swiper.emit("update");
  }
  changeDirection(newDirection, needUpdate) {
    if (needUpdate === void 0) {
      needUpdate = true;
    }
    const swiper = this;
    const currentDirection = swiper.params.direction;
    if (!newDirection) {
      newDirection = currentDirection === "horizontal" ? "vertical" : "horizontal";
    }
    if (newDirection === currentDirection || newDirection !== "horizontal" && newDirection !== "vertical") {
      return swiper;
    }
    swiper.el.classList.remove(`${swiper.params.containerModifierClass}${currentDirection}`);
    swiper.el.classList.add(`${swiper.params.containerModifierClass}${newDirection}`);
    swiper.emitContainerClasses();
    swiper.params.direction = newDirection;
    swiper.slides.forEach((slideEl) => {
      if (newDirection === "vertical") {
        slideEl.style.width = "";
      } else {
        slideEl.style.height = "";
      }
    });
    swiper.emit("changeDirection");
    if (needUpdate)
      swiper.update();
    return swiper;
  }
  changeLanguageDirection(direction) {
    const swiper = this;
    if (swiper.rtl && direction === "rtl" || !swiper.rtl && direction === "ltr")
      return;
    swiper.rtl = direction === "rtl";
    swiper.rtlTranslate = swiper.params.direction === "horizontal" && swiper.rtl;
    if (swiper.rtl) {
      swiper.el.classList.add(`${swiper.params.containerModifierClass}rtl`);
      swiper.el.dir = "rtl";
    } else {
      swiper.el.classList.remove(`${swiper.params.containerModifierClass}rtl`);
      swiper.el.dir = "ltr";
    }
    swiper.update();
  }
  mount(element) {
    const swiper = this;
    if (swiper.mounted)
      return true;
    let el = element || swiper.params.el;
    if (typeof el === "string") {
      el = document.querySelector(el);
    }
    if (!el) {
      return false;
    }
    el.swiper = swiper;
    if (el.parentNode && el.parentNode.host && el.parentNode.host.nodeName === swiper.params.swiperElementNodeName.toUpperCase()) {
      swiper.isElement = true;
    }
    const getWrapperSelector = () => {
      return `.${(swiper.params.wrapperClass || "").trim().split(" ").join(".")}`;
    };
    const getWrapper = () => {
      if (el && el.shadowRoot && el.shadowRoot.querySelector) {
        const res = el.shadowRoot.querySelector(getWrapperSelector());
        return res;
      }
      return elementChildren(el, getWrapperSelector())[0];
    };
    let wrapperEl = getWrapper();
    if (!wrapperEl && swiper.params.createElements) {
      wrapperEl = createElement("div", swiper.params.wrapperClass);
      el.append(wrapperEl);
      elementChildren(el, `.${swiper.params.slideClass}`).forEach((slideEl) => {
        wrapperEl.append(slideEl);
      });
    }
    Object.assign(swiper, {
      el,
      wrapperEl,
      slidesEl: swiper.isElement && !el.parentNode.host.slideSlots ? el.parentNode.host : wrapperEl,
      hostEl: swiper.isElement ? el.parentNode.host : el,
      mounted: true,
      // RTL
      rtl: el.dir.toLowerCase() === "rtl" || elementStyle(el, "direction") === "rtl",
      rtlTranslate: swiper.params.direction === "horizontal" && (el.dir.toLowerCase() === "rtl" || elementStyle(el, "direction") === "rtl"),
      wrongRTL: elementStyle(wrapperEl, "display") === "-webkit-box"
    });
    return true;
  }
  init(el) {
    const swiper = this;
    if (swiper.initialized)
      return swiper;
    const mounted = swiper.mount(el);
    if (mounted === false)
      return swiper;
    swiper.emit("beforeInit");
    if (swiper.params.breakpoints) {
      swiper.setBreakpoint();
    }
    swiper.addClasses();
    swiper.updateSize();
    swiper.updateSlides();
    if (swiper.params.watchOverflow) {
      swiper.checkOverflow();
    }
    if (swiper.params.grabCursor && swiper.enabled) {
      swiper.setGrabCursor();
    }
    if (swiper.params.loop && swiper.virtual && swiper.params.virtual.enabled) {
      swiper.slideTo(swiper.params.initialSlide + swiper.virtual.slidesBefore, 0, swiper.params.runCallbacksOnInit, false, true);
    } else {
      swiper.slideTo(swiper.params.initialSlide, 0, swiper.params.runCallbacksOnInit, false, true);
    }
    if (swiper.params.loop) {
      swiper.loopCreate();
    }
    swiper.attachEvents();
    const lazyElements = [...swiper.el.querySelectorAll('[loading="lazy"]')];
    if (swiper.isElement) {
      lazyElements.push(...swiper.hostEl.querySelectorAll('[loading="lazy"]'));
    }
    lazyElements.forEach((imageEl) => {
      if (imageEl.complete) {
        processLazyPreloader(swiper, imageEl);
      } else {
        imageEl.addEventListener("load", (e) => {
          processLazyPreloader(swiper, e.target);
        });
      }
    });
    preload(swiper);
    swiper.initialized = true;
    preload(swiper);
    swiper.emit("init");
    swiper.emit("afterInit");
    return swiper;
  }
  destroy(deleteInstance, cleanStyles) {
    if (deleteInstance === void 0) {
      deleteInstance = true;
    }
    if (cleanStyles === void 0) {
      cleanStyles = true;
    }
    const swiper = this;
    const {
      params,
      el,
      wrapperEl,
      slides
    } = swiper;
    if (typeof swiper.params === "undefined" || swiper.destroyed) {
      return null;
    }
    swiper.emit("beforeDestroy");
    swiper.initialized = false;
    swiper.detachEvents();
    if (params.loop) {
      swiper.loopDestroy();
    }
    if (cleanStyles) {
      swiper.removeClasses();
      if (el && typeof el !== "string") {
        el.removeAttribute("style");
      }
      if (wrapperEl) {
        wrapperEl.removeAttribute("style");
      }
      if (slides && slides.length) {
        slides.forEach((slideEl) => {
          slideEl.classList.remove(params.slideVisibleClass, params.slideFullyVisibleClass, params.slideActiveClass, params.slideNextClass, params.slidePrevClass);
          slideEl.removeAttribute("style");
          slideEl.removeAttribute("data-swiper-slide-index");
        });
      }
    }
    swiper.emit("destroy");
    Object.keys(swiper.eventsListeners).forEach((eventName) => {
      swiper.off(eventName);
    });
    if (deleteInstance !== false) {
      if (swiper.el && typeof swiper.el !== "string") {
        swiper.el.swiper = null;
      }
      deleteProps(swiper);
    }
    swiper.destroyed = true;
    return null;
  }
  static extendDefaults(newDefaults) {
    extend2(extendedDefaults, newDefaults);
  }
  static get extendedDefaults() {
    return extendedDefaults;
  }
  static get defaults() {
    return defaults;
  }
  static installModule(mod) {
    if (!_Swiper.prototype.__modules__)
      _Swiper.prototype.__modules__ = [];
    const modules = _Swiper.prototype.__modules__;
    if (typeof mod === "function" && modules.indexOf(mod) < 0) {
      modules.push(mod);
    }
  }
  static use(module) {
    if (Array.isArray(module)) {
      module.forEach((m) => _Swiper.installModule(m));
      return _Swiper;
    }
    _Swiper.installModule(module);
    return _Swiper;
  }
};
Object.keys(prototypes).forEach((prototypeGroup) => {
  Object.keys(prototypes[prototypeGroup]).forEach((protoMethod) => {
    Swiper.prototype[protoMethod] = prototypes[prototypeGroup][protoMethod];
  });
});
Swiper.use([Resize, Observer]);

// node_modules/swiper/shared/create-element-if-not-defined.mjs
function createElementIfNotDefined(swiper, originalParams, params, checkProps) {
  if (swiper.params.createElements) {
    Object.keys(checkProps).forEach((key) => {
      if (!params[key] && params.auto === true) {
        let element = elementChildren(swiper.el, `.${checkProps[key]}`)[0];
        if (!element) {
          element = createElement("div", checkProps[key]);
          element.className = checkProps[key];
          swiper.el.append(element);
        }
        params[key] = element;
        originalParams[key] = element;
      }
    });
  }
  return params;
}

// node_modules/swiper/modules/navigation.mjs
function Navigation(_ref) {
  let {
    swiper,
    extendParams,
    on,
    emit
  } = _ref;
  extendParams({
    navigation: {
      nextEl: null,
      prevEl: null,
      hideOnClick: false,
      disabledClass: "swiper-button-disabled",
      hiddenClass: "swiper-button-hidden",
      lockClass: "swiper-button-lock",
      navigationDisabledClass: "swiper-navigation-disabled"
    }
  });
  swiper.navigation = {
    nextEl: null,
    prevEl: null
  };
  function getEl(el) {
    let res;
    if (el && typeof el === "string" && swiper.isElement) {
      res = swiper.el.querySelector(el) || swiper.hostEl.querySelector(el);
      if (res)
        return res;
    }
    if (el) {
      if (typeof el === "string")
        res = [...document.querySelectorAll(el)];
      if (swiper.params.uniqueNavElements && typeof el === "string" && res && res.length > 1 && swiper.el.querySelectorAll(el).length === 1) {
        res = swiper.el.querySelector(el);
      } else if (res && res.length === 1) {
        res = res[0];
      }
    }
    if (el && !res)
      return el;
    return res;
  }
  function toggleEl(el, disabled) {
    const params = swiper.params.navigation;
    el = makeElementsArray(el);
    el.forEach((subEl) => {
      if (subEl) {
        subEl.classList[disabled ? "add" : "remove"](...params.disabledClass.split(" "));
        if (subEl.tagName === "BUTTON")
          subEl.disabled = disabled;
        if (swiper.params.watchOverflow && swiper.enabled) {
          subEl.classList[swiper.isLocked ? "add" : "remove"](params.lockClass);
        }
      }
    });
  }
  function update2() {
    const {
      nextEl,
      prevEl
    } = swiper.navigation;
    if (swiper.params.loop) {
      toggleEl(prevEl, false);
      toggleEl(nextEl, false);
      return;
    }
    toggleEl(prevEl, swiper.isBeginning && !swiper.params.rewind);
    toggleEl(nextEl, swiper.isEnd && !swiper.params.rewind);
  }
  function onPrevClick(e) {
    e.preventDefault();
    if (swiper.isBeginning && !swiper.params.loop && !swiper.params.rewind)
      return;
    swiper.slidePrev();
    emit("navigationPrev");
  }
  function onNextClick(e) {
    e.preventDefault();
    if (swiper.isEnd && !swiper.params.loop && !swiper.params.rewind)
      return;
    swiper.slideNext();
    emit("navigationNext");
  }
  function init() {
    const params = swiper.params.navigation;
    swiper.params.navigation = createElementIfNotDefined(swiper, swiper.originalParams.navigation, swiper.params.navigation, {
      nextEl: "swiper-button-next",
      prevEl: "swiper-button-prev"
    });
    if (!(params.nextEl || params.prevEl))
      return;
    let nextEl = getEl(params.nextEl);
    let prevEl = getEl(params.prevEl);
    Object.assign(swiper.navigation, {
      nextEl,
      prevEl
    });
    nextEl = makeElementsArray(nextEl);
    prevEl = makeElementsArray(prevEl);
    const initButton = (el, dir) => {
      if (el) {
        el.addEventListener("click", dir === "next" ? onNextClick : onPrevClick);
      }
      if (!swiper.enabled && el) {
        el.classList.add(...params.lockClass.split(" "));
      }
    };
    nextEl.forEach((el) => initButton(el, "next"));
    prevEl.forEach((el) => initButton(el, "prev"));
  }
  function destroy() {
    let {
      nextEl,
      prevEl
    } = swiper.navigation;
    nextEl = makeElementsArray(nextEl);
    prevEl = makeElementsArray(prevEl);
    const destroyButton = (el, dir) => {
      el.removeEventListener("click", dir === "next" ? onNextClick : onPrevClick);
      el.classList.remove(...swiper.params.navigation.disabledClass.split(" "));
    };
    nextEl.forEach((el) => destroyButton(el, "next"));
    prevEl.forEach((el) => destroyButton(el, "prev"));
  }
  on("init", () => {
    if (swiper.params.navigation.enabled === false) {
      disable();
    } else {
      init();
      update2();
    }
  });
  on("toEdge fromEdge lock unlock", () => {
    update2();
  });
  on("destroy", () => {
    destroy();
  });
  on("enable disable", () => {
    let {
      nextEl,
      prevEl
    } = swiper.navigation;
    nextEl = makeElementsArray(nextEl);
    prevEl = makeElementsArray(prevEl);
    if (swiper.enabled) {
      update2();
      return;
    }
    [...nextEl, ...prevEl].filter((el) => !!el).forEach((el) => el.classList.add(swiper.params.navigation.lockClass));
  });
  on("click", (_s, e) => {
    let {
      nextEl,
      prevEl
    } = swiper.navigation;
    nextEl = makeElementsArray(nextEl);
    prevEl = makeElementsArray(prevEl);
    const targetEl = e.target;
    let targetIsButton = prevEl.includes(targetEl) || nextEl.includes(targetEl);
    if (swiper.isElement && !targetIsButton) {
      const path = e.path || e.composedPath && e.composedPath();
      if (path) {
        targetIsButton = path.find((pathEl) => nextEl.includes(pathEl) || prevEl.includes(pathEl));
      }
    }
    if (swiper.params.navigation.hideOnClick && !targetIsButton) {
      if (swiper.pagination && swiper.params.pagination && swiper.params.pagination.clickable && (swiper.pagination.el === targetEl || swiper.pagination.el.contains(targetEl)))
        return;
      let isHidden;
      if (nextEl.length) {
        isHidden = nextEl[0].classList.contains(swiper.params.navigation.hiddenClass);
      } else if (prevEl.length) {
        isHidden = prevEl[0].classList.contains(swiper.params.navigation.hiddenClass);
      }
      if (isHidden === true) {
        emit("navigationShow");
      } else {
        emit("navigationHide");
      }
      [...nextEl, ...prevEl].filter((el) => !!el).forEach((el) => el.classList.toggle(swiper.params.navigation.hiddenClass));
    }
  });
  const enable = () => {
    swiper.el.classList.remove(...swiper.params.navigation.navigationDisabledClass.split(" "));
    init();
    update2();
  };
  const disable = () => {
    swiper.el.classList.add(...swiper.params.navigation.navigationDisabledClass.split(" "));
    destroy();
  };
  Object.assign(swiper.navigation, {
    enable,
    disable,
    update: update2,
    init,
    destroy
  });
}

// node_modules/swiper/modules/thumbs.mjs
function Thumb(_ref) {
  let {
    swiper,
    extendParams,
    on
  } = _ref;
  extendParams({
    thumbs: {
      swiper: null,
      multipleActiveThumbs: true,
      autoScrollOffset: 0,
      slideThumbActiveClass: "swiper-slide-thumb-active",
      thumbsContainerClass: "swiper-thumbs"
    }
  });
  let initialized = false;
  let swiperCreated = false;
  swiper.thumbs = {
    swiper: null
  };
  function onThumbClick() {
    const thumbsSwiper = swiper.thumbs.swiper;
    if (!thumbsSwiper || thumbsSwiper.destroyed)
      return;
    const clickedIndex = thumbsSwiper.clickedIndex;
    const clickedSlide = thumbsSwiper.clickedSlide;
    if (clickedSlide && clickedSlide.classList.contains(swiper.params.thumbs.slideThumbActiveClass))
      return;
    if (typeof clickedIndex === "undefined" || clickedIndex === null)
      return;
    let slideToIndex;
    if (thumbsSwiper.params.loop) {
      slideToIndex = parseInt(thumbsSwiper.clickedSlide.getAttribute("data-swiper-slide-index"), 10);
    } else {
      slideToIndex = clickedIndex;
    }
    if (swiper.params.loop) {
      swiper.slideToLoop(slideToIndex);
    } else {
      swiper.slideTo(slideToIndex);
    }
  }
  function init() {
    const {
      thumbs: thumbsParams
    } = swiper.params;
    if (initialized)
      return false;
    initialized = true;
    const SwiperClass = swiper.constructor;
    if (thumbsParams.swiper instanceof SwiperClass) {
      swiper.thumbs.swiper = thumbsParams.swiper;
      Object.assign(swiper.thumbs.swiper.originalParams, {
        watchSlidesProgress: true,
        slideToClickedSlide: false
      });
      Object.assign(swiper.thumbs.swiper.params, {
        watchSlidesProgress: true,
        slideToClickedSlide: false
      });
      swiper.thumbs.swiper.update();
    } else if (isObject2(thumbsParams.swiper)) {
      const thumbsSwiperParams = Object.assign({}, thumbsParams.swiper);
      Object.assign(thumbsSwiperParams, {
        watchSlidesProgress: true,
        slideToClickedSlide: false
      });
      swiper.thumbs.swiper = new SwiperClass(thumbsSwiperParams);
      swiperCreated = true;
    }
    swiper.thumbs.swiper.el.classList.add(swiper.params.thumbs.thumbsContainerClass);
    swiper.thumbs.swiper.on("tap", onThumbClick);
    return true;
  }
  function update2(initial) {
    const thumbsSwiper = swiper.thumbs.swiper;
    if (!thumbsSwiper || thumbsSwiper.destroyed)
      return;
    const slidesPerView = thumbsSwiper.params.slidesPerView === "auto" ? thumbsSwiper.slidesPerViewDynamic() : thumbsSwiper.params.slidesPerView;
    let thumbsToActivate = 1;
    const thumbActiveClass = swiper.params.thumbs.slideThumbActiveClass;
    if (swiper.params.slidesPerView > 1 && !swiper.params.centeredSlides) {
      thumbsToActivate = swiper.params.slidesPerView;
    }
    if (!swiper.params.thumbs.multipleActiveThumbs) {
      thumbsToActivate = 1;
    }
    thumbsToActivate = Math.floor(thumbsToActivate);
    thumbsSwiper.slides.forEach((slideEl) => slideEl.classList.remove(thumbActiveClass));
    if (thumbsSwiper.params.loop || thumbsSwiper.params.virtual && thumbsSwiper.params.virtual.enabled) {
      for (let i = 0; i < thumbsToActivate; i += 1) {
        elementChildren(thumbsSwiper.slidesEl, `[data-swiper-slide-index="${swiper.realIndex + i}"]`).forEach((slideEl) => {
          slideEl.classList.add(thumbActiveClass);
        });
      }
    } else {
      for (let i = 0; i < thumbsToActivate; i += 1) {
        if (thumbsSwiper.slides[swiper.realIndex + i]) {
          thumbsSwiper.slides[swiper.realIndex + i].classList.add(thumbActiveClass);
        }
      }
    }
    const autoScrollOffset = swiper.params.thumbs.autoScrollOffset;
    const useOffset = autoScrollOffset && !thumbsSwiper.params.loop;
    if (swiper.realIndex !== thumbsSwiper.realIndex || useOffset) {
      const currentThumbsIndex = thumbsSwiper.activeIndex;
      let newThumbsIndex;
      let direction;
      if (thumbsSwiper.params.loop) {
        const newThumbsSlide = thumbsSwiper.slides.filter((slideEl) => slideEl.getAttribute("data-swiper-slide-index") === `${swiper.realIndex}`)[0];
        newThumbsIndex = thumbsSwiper.slides.indexOf(newThumbsSlide);
        direction = swiper.activeIndex > swiper.previousIndex ? "next" : "prev";
      } else {
        newThumbsIndex = swiper.realIndex;
        direction = newThumbsIndex > swiper.previousIndex ? "next" : "prev";
      }
      if (useOffset) {
        newThumbsIndex += direction === "next" ? autoScrollOffset : -1 * autoScrollOffset;
      }
      if (thumbsSwiper.visibleSlidesIndexes && thumbsSwiper.visibleSlidesIndexes.indexOf(newThumbsIndex) < 0) {
        if (thumbsSwiper.params.centeredSlides) {
          if (newThumbsIndex > currentThumbsIndex) {
            newThumbsIndex = newThumbsIndex - Math.floor(slidesPerView / 2) + 1;
          } else {
            newThumbsIndex = newThumbsIndex + Math.floor(slidesPerView / 2) - 1;
          }
        } else if (newThumbsIndex > currentThumbsIndex && thumbsSwiper.params.slidesPerGroup === 1)
          ;
        thumbsSwiper.slideTo(newThumbsIndex, initial ? 0 : void 0);
      }
    }
  }
  on("beforeInit", () => {
    const {
      thumbs
    } = swiper.params;
    if (!thumbs || !thumbs.swiper)
      return;
    if (typeof thumbs.swiper === "string" || thumbs.swiper instanceof HTMLElement) {
      const document2 = getDocument();
      const getThumbsElementAndInit = () => {
        const thumbsElement = typeof thumbs.swiper === "string" ? document2.querySelector(thumbs.swiper) : thumbs.swiper;
        if (thumbsElement && thumbsElement.swiper) {
          thumbs.swiper = thumbsElement.swiper;
          init();
          update2(true);
        } else if (thumbsElement) {
          const eventName = `${swiper.params.eventsPrefix}init`;
          const onThumbsSwiper = (e) => {
            thumbs.swiper = e.detail[0];
            thumbsElement.removeEventListener(eventName, onThumbsSwiper);
            init();
            update2(true);
            thumbs.swiper.update();
            swiper.update();
          };
          thumbsElement.addEventListener(eventName, onThumbsSwiper);
        }
        return thumbsElement;
      };
      const watchForThumbsToAppear = () => {
        if (swiper.destroyed)
          return;
        const thumbsElement = getThumbsElementAndInit();
        if (!thumbsElement) {
          requestAnimationFrame(watchForThumbsToAppear);
        }
      };
      requestAnimationFrame(watchForThumbsToAppear);
    } else {
      init();
      update2(true);
    }
  });
  on("slideChange update resize observerUpdate", () => {
    update2();
  });
  on("setTransition", (_s, duration) => {
    const thumbsSwiper = swiper.thumbs.swiper;
    if (!thumbsSwiper || thumbsSwiper.destroyed)
      return;
    thumbsSwiper.setTransition(duration);
  });
  on("beforeDestroy", () => {
    const thumbsSwiper = swiper.thumbs.swiper;
    if (!thumbsSwiper || thumbsSwiper.destroyed)
      return;
    if (swiperCreated) {
      thumbsSwiper.destroy();
    }
  });
  Object.assign(swiper.thumbs, {
    init,
    update: update2
  });
}

// node_modules/swiper/modules/free-mode.mjs
function freeMode(_ref) {
  let {
    swiper,
    extendParams,
    emit,
    once
  } = _ref;
  extendParams({
    freeMode: {
      enabled: false,
      momentum: true,
      momentumRatio: 1,
      momentumBounce: true,
      momentumBounceRatio: 1,
      momentumVelocityRatio: 1,
      sticky: false,
      minimumVelocity: 0.02
    }
  });
  function onTouchStart2() {
    if (swiper.params.cssMode)
      return;
    const translate2 = swiper.getTranslate();
    swiper.setTranslate(translate2);
    swiper.setTransition(0);
    swiper.touchEventsData.velocities.length = 0;
    swiper.freeMode.onTouchEnd({
      currentPos: swiper.rtl ? swiper.translate : -swiper.translate
    });
  }
  function onTouchMove2() {
    if (swiper.params.cssMode)
      return;
    const {
      touchEventsData: data,
      touches
    } = swiper;
    if (data.velocities.length === 0) {
      data.velocities.push({
        position: touches[swiper.isHorizontal() ? "startX" : "startY"],
        time: data.touchStartTime
      });
    }
    data.velocities.push({
      position: touches[swiper.isHorizontal() ? "currentX" : "currentY"],
      time: now()
    });
  }
  function onTouchEnd2(_ref2) {
    let {
      currentPos
    } = _ref2;
    if (swiper.params.cssMode)
      return;
    const {
      params,
      wrapperEl,
      rtlTranslate: rtl,
      snapGrid,
      touchEventsData: data
    } = swiper;
    const touchEndTime = now();
    const timeDiff = touchEndTime - data.touchStartTime;
    if (currentPos < -swiper.minTranslate()) {
      swiper.slideTo(swiper.activeIndex);
      return;
    }
    if (currentPos > -swiper.maxTranslate()) {
      if (swiper.slides.length < snapGrid.length) {
        swiper.slideTo(snapGrid.length - 1);
      } else {
        swiper.slideTo(swiper.slides.length - 1);
      }
      return;
    }
    if (params.freeMode.momentum) {
      if (data.velocities.length > 1) {
        const lastMoveEvent = data.velocities.pop();
        const velocityEvent = data.velocities.pop();
        const distance = lastMoveEvent.position - velocityEvent.position;
        const time = lastMoveEvent.time - velocityEvent.time;
        swiper.velocity = distance / time;
        swiper.velocity /= 2;
        if (Math.abs(swiper.velocity) < params.freeMode.minimumVelocity) {
          swiper.velocity = 0;
        }
        if (time > 150 || now() - lastMoveEvent.time > 300) {
          swiper.velocity = 0;
        }
      } else {
        swiper.velocity = 0;
      }
      swiper.velocity *= params.freeMode.momentumVelocityRatio;
      data.velocities.length = 0;
      let momentumDuration = 1e3 * params.freeMode.momentumRatio;
      const momentumDistance = swiper.velocity * momentumDuration;
      let newPosition = swiper.translate + momentumDistance;
      if (rtl)
        newPosition = -newPosition;
      let doBounce = false;
      let afterBouncePosition;
      const bounceAmount = Math.abs(swiper.velocity) * 20 * params.freeMode.momentumBounceRatio;
      let needsLoopFix;
      if (newPosition < swiper.maxTranslate()) {
        if (params.freeMode.momentumBounce) {
          if (newPosition + swiper.maxTranslate() < -bounceAmount) {
            newPosition = swiper.maxTranslate() - bounceAmount;
          }
          afterBouncePosition = swiper.maxTranslate();
          doBounce = true;
          data.allowMomentumBounce = true;
        } else {
          newPosition = swiper.maxTranslate();
        }
        if (params.loop && params.centeredSlides)
          needsLoopFix = true;
      } else if (newPosition > swiper.minTranslate()) {
        if (params.freeMode.momentumBounce) {
          if (newPosition - swiper.minTranslate() > bounceAmount) {
            newPosition = swiper.minTranslate() + bounceAmount;
          }
          afterBouncePosition = swiper.minTranslate();
          doBounce = true;
          data.allowMomentumBounce = true;
        } else {
          newPosition = swiper.minTranslate();
        }
        if (params.loop && params.centeredSlides)
          needsLoopFix = true;
      } else if (params.freeMode.sticky) {
        let nextSlide;
        for (let j = 0; j < snapGrid.length; j += 1) {
          if (snapGrid[j] > -newPosition) {
            nextSlide = j;
            break;
          }
        }
        if (Math.abs(snapGrid[nextSlide] - newPosition) < Math.abs(snapGrid[nextSlide - 1] - newPosition) || swiper.swipeDirection === "next") {
          newPosition = snapGrid[nextSlide];
        } else {
          newPosition = snapGrid[nextSlide - 1];
        }
        newPosition = -newPosition;
      }
      if (needsLoopFix) {
        once("transitionEnd", () => {
          swiper.loopFix();
        });
      }
      if (swiper.velocity !== 0) {
        if (rtl) {
          momentumDuration = Math.abs((-newPosition - swiper.translate) / swiper.velocity);
        } else {
          momentumDuration = Math.abs((newPosition - swiper.translate) / swiper.velocity);
        }
        if (params.freeMode.sticky) {
          const moveDistance = Math.abs((rtl ? -newPosition : newPosition) - swiper.translate);
          const currentSlideSize = swiper.slidesSizesGrid[swiper.activeIndex];
          if (moveDistance < currentSlideSize) {
            momentumDuration = params.speed;
          } else if (moveDistance < 2 * currentSlideSize) {
            momentumDuration = params.speed * 1.5;
          } else {
            momentumDuration = params.speed * 2.5;
          }
        }
      } else if (params.freeMode.sticky) {
        swiper.slideToClosest();
        return;
      }
      if (params.freeMode.momentumBounce && doBounce) {
        swiper.updateProgress(afterBouncePosition);
        swiper.setTransition(momentumDuration);
        swiper.setTranslate(newPosition);
        swiper.transitionStart(true, swiper.swipeDirection);
        swiper.animating = true;
        elementTransitionEnd(wrapperEl, () => {
          if (!swiper || swiper.destroyed || !data.allowMomentumBounce)
            return;
          emit("momentumBounce");
          swiper.setTransition(params.speed);
          setTimeout(() => {
            swiper.setTranslate(afterBouncePosition);
            elementTransitionEnd(wrapperEl, () => {
              if (!swiper || swiper.destroyed)
                return;
              swiper.transitionEnd();
            });
          }, 0);
        });
      } else if (swiper.velocity) {
        emit("_freeModeNoMomentumRelease");
        swiper.updateProgress(newPosition);
        swiper.setTransition(momentumDuration);
        swiper.setTranslate(newPosition);
        swiper.transitionStart(true, swiper.swipeDirection);
        if (!swiper.animating) {
          swiper.animating = true;
          elementTransitionEnd(wrapperEl, () => {
            if (!swiper || swiper.destroyed)
              return;
            swiper.transitionEnd();
          });
        }
      } else {
        swiper.updateProgress(newPosition);
      }
      swiper.updateActiveIndex();
      swiper.updateSlidesClasses();
    } else if (params.freeMode.sticky) {
      swiper.slideToClosest();
      return;
    } else if (params.freeMode) {
      emit("_freeModeNoMomentumRelease");
    }
    if (!params.freeMode.momentum || timeDiff >= params.longSwipesMs) {
      emit("_freeModeStaticRelease");
      swiper.updateProgress();
      swiper.updateActiveIndex();
      swiper.updateSlidesClasses();
    }
  }
  Object.assign(swiper, {
    freeMode: {
      onTouchStart: onTouchStart2,
      onTouchMove: onTouchMove2,
      onTouchEnd: onTouchEnd2
    }
  });
}

// resources/js/components/swiper.js
function supportSwiper({ swiperIsSquare, hasThumb, thumbPosition, thumbScale }) {
  return {
    swiper: null,
    thumbSwiper: null,
    swiperIsSquare,
    hasThumb,
    thumbPosition,
    thumbScale,
    swiperHeight: null,
    init: function() {
      let swiperOptions = {
        modules: [freeMode, Navigation, Thumb],
        loop: true,
        spaceBetween: 10,
        // 滑动时两个幻灯片之间的距离 px
        slidesPerView: 1,
        // 可视区域可见幻灯片数量
        navigation: {
          nextEl: ".swiper-button-next",
          prevEl: ".swiper-button-prev"
        }
      };
      if (this.hasThumb) {
        this.thumbSwiper = new Swiper(".detail-swiper-thumbs", {
          modules: [freeMode, Navigation, Thumb],
          loop: true,
          spaceBetween: 10,
          // 滑动时两个幻灯片之间的距离 px
          slidesPerView: 6,
          // 可视区域可见幻灯片数量
          freeMode: true,
          watchSlidesProgress: true
          // 启用此功能以计算每个幻灯片的进度和可见性(视口中的幻灯片将有额外的可见类
        });
        swiperOptions["thumbs"] = {
          swiper: this.thumbSwiper
        };
      }
      this.swiper = new Swiper(".detail-swiper", swiperOptions);
    },
    setSwiperHeight: function() {
      if (!this.swiperIsSquare) {
        this.swiperHeight = this.$height;
      } else {
        if (this.hasThumb) {
          if (["left", "right"].includes(this.thumbPosition)) {
            this.swiperHeight = (this.$width * (100 - this.thumbScale) / 100).toFixed(2);
          } else {
            this.swiperHeight = (this.$width / ((100 - this.thumbScale) / 100)).toFixed(2);
          }
        } else {
          this.swiperHeight = this.$width;
        }
      }
    }
  };
}
export {
  supportSwiper as default
};
//# sourceMappingURL=data:application/json;base64,ewogICJ2ZXJzaW9uIjogMywKICAic291cmNlcyI6IFsiLi4vLi4vLi4vbm9kZV9tb2R1bGVzL3N3aXBlci9zaGFyZWQvc3NyLXdpbmRvdy5lc20ubWpzIiwgIi4uLy4uLy4uL25vZGVfbW9kdWxlcy9zd2lwZXIvc2hhcmVkL3V0aWxzLm1qcyIsICIuLi8uLi8uLi9ub2RlX21vZHVsZXMvc3dpcGVyL3NoYXJlZC9zd2lwZXItY29yZS5tanMiLCAiLi4vLi4vLi4vbm9kZV9tb2R1bGVzL3N3aXBlci9zaGFyZWQvY3JlYXRlLWVsZW1lbnQtaWYtbm90LWRlZmluZWQubWpzIiwgIi4uLy4uLy4uL25vZGVfbW9kdWxlcy9zd2lwZXIvbW9kdWxlcy9uYXZpZ2F0aW9uLm1qcyIsICIuLi8uLi8uLi9ub2RlX21vZHVsZXMvc3dpcGVyL21vZHVsZXMvdGh1bWJzLm1qcyIsICIuLi8uLi8uLi9ub2RlX21vZHVsZXMvc3dpcGVyL21vZHVsZXMvZnJlZS1tb2RlLm1qcyIsICIuLi8uLi9qcy9jb21wb25lbnRzL3N3aXBlci5qcyJdLAogICJzb3VyY2VzQ29udGVudCI6IFsiLyoqXG4gKiBTU1IgV2luZG93IDQuMC4yXG4gKiBCZXR0ZXIgaGFuZGxpbmcgZm9yIHdpbmRvdyBvYmplY3QgaW4gU1NSIGVudmlyb25tZW50XG4gKiBodHRwczovL2dpdGh1Yi5jb20vbm9saW1pdHM0d2ViL3Nzci13aW5kb3dcbiAqXG4gKiBDb3B5cmlnaHQgMjAyMSwgVmxhZGltaXIgS2hhcmxhbXBpZGlcbiAqXG4gKiBMaWNlbnNlZCB1bmRlciBNSVRcbiAqXG4gKiBSZWxlYXNlZCBvbjogRGVjZW1iZXIgMTMsIDIwMjFcbiAqL1xuLyogZXNsaW50LWRpc2FibGUgbm8tcGFyYW0tcmVhc3NpZ24gKi9cbmZ1bmN0aW9uIGlzT2JqZWN0KG9iaikge1xuICByZXR1cm4gb2JqICE9PSBudWxsICYmIHR5cGVvZiBvYmogPT09ICdvYmplY3QnICYmICdjb25zdHJ1Y3RvcicgaW4gb2JqICYmIG9iai5jb25zdHJ1Y3RvciA9PT0gT2JqZWN0O1xufVxuZnVuY3Rpb24gZXh0ZW5kKHRhcmdldCwgc3JjKSB7XG4gIGlmICh0YXJnZXQgPT09IHZvaWQgMCkge1xuICAgIHRhcmdldCA9IHt9O1xuICB9XG4gIGlmIChzcmMgPT09IHZvaWQgMCkge1xuICAgIHNyYyA9IHt9O1xuICB9XG4gIE9iamVjdC5rZXlzKHNyYykuZm9yRWFjaChrZXkgPT4ge1xuICAgIGlmICh0eXBlb2YgdGFyZ2V0W2tleV0gPT09ICd1bmRlZmluZWQnKSB0YXJnZXRba2V5XSA9IHNyY1trZXldO2Vsc2UgaWYgKGlzT2JqZWN0KHNyY1trZXldKSAmJiBpc09iamVjdCh0YXJnZXRba2V5XSkgJiYgT2JqZWN0LmtleXMoc3JjW2tleV0pLmxlbmd0aCA+IDApIHtcbiAgICAgIGV4dGVuZCh0YXJnZXRba2V5XSwgc3JjW2tleV0pO1xuICAgIH1cbiAgfSk7XG59XG5jb25zdCBzc3JEb2N1bWVudCA9IHtcbiAgYm9keToge30sXG4gIGFkZEV2ZW50TGlzdGVuZXIoKSB7fSxcbiAgcmVtb3ZlRXZlbnRMaXN0ZW5lcigpIHt9LFxuICBhY3RpdmVFbGVtZW50OiB7XG4gICAgYmx1cigpIHt9LFxuICAgIG5vZGVOYW1lOiAnJ1xuICB9LFxuICBxdWVyeVNlbGVjdG9yKCkge1xuICAgIHJldHVybiBudWxsO1xuICB9LFxuICBxdWVyeVNlbGVjdG9yQWxsKCkge1xuICAgIHJldHVybiBbXTtcbiAgfSxcbiAgZ2V0RWxlbWVudEJ5SWQoKSB7XG4gICAgcmV0dXJuIG51bGw7XG4gIH0sXG4gIGNyZWF0ZUV2ZW50KCkge1xuICAgIHJldHVybiB7XG4gICAgICBpbml0RXZlbnQoKSB7fVxuICAgIH07XG4gIH0sXG4gIGNyZWF0ZUVsZW1lbnQoKSB7XG4gICAgcmV0dXJuIHtcbiAgICAgIGNoaWxkcmVuOiBbXSxcbiAgICAgIGNoaWxkTm9kZXM6IFtdLFxuICAgICAgc3R5bGU6IHt9LFxuICAgICAgc2V0QXR0cmlidXRlKCkge30sXG4gICAgICBnZXRFbGVtZW50c0J5VGFnTmFtZSgpIHtcbiAgICAgICAgcmV0dXJuIFtdO1xuICAgICAgfVxuICAgIH07XG4gIH0sXG4gIGNyZWF0ZUVsZW1lbnROUygpIHtcbiAgICByZXR1cm4ge307XG4gIH0sXG4gIGltcG9ydE5vZGUoKSB7XG4gICAgcmV0dXJuIG51bGw7XG4gIH0sXG4gIGxvY2F0aW9uOiB7XG4gICAgaGFzaDogJycsXG4gICAgaG9zdDogJycsXG4gICAgaG9zdG5hbWU6ICcnLFxuICAgIGhyZWY6ICcnLFxuICAgIG9yaWdpbjogJycsXG4gICAgcGF0aG5hbWU6ICcnLFxuICAgIHByb3RvY29sOiAnJyxcbiAgICBzZWFyY2g6ICcnXG4gIH1cbn07XG5mdW5jdGlvbiBnZXREb2N1bWVudCgpIHtcbiAgY29uc3QgZG9jID0gdHlwZW9mIGRvY3VtZW50ICE9PSAndW5kZWZpbmVkJyA/IGRvY3VtZW50IDoge307XG4gIGV4dGVuZChkb2MsIHNzckRvY3VtZW50KTtcbiAgcmV0dXJuIGRvYztcbn1cbmNvbnN0IHNzcldpbmRvdyA9IHtcbiAgZG9jdW1lbnQ6IHNzckRvY3VtZW50LFxuICBuYXZpZ2F0b3I6IHtcbiAgICB1c2VyQWdlbnQ6ICcnXG4gIH0sXG4gIGxvY2F0aW9uOiB7XG4gICAgaGFzaDogJycsXG4gICAgaG9zdDogJycsXG4gICAgaG9zdG5hbWU6ICcnLFxuICAgIGhyZWY6ICcnLFxuICAgIG9yaWdpbjogJycsXG4gICAgcGF0aG5hbWU6ICcnLFxuICAgIHByb3RvY29sOiAnJyxcbiAgICBzZWFyY2g6ICcnXG4gIH0sXG4gIGhpc3Rvcnk6IHtcbiAgICByZXBsYWNlU3RhdGUoKSB7fSxcbiAgICBwdXNoU3RhdGUoKSB7fSxcbiAgICBnbygpIHt9LFxuICAgIGJhY2soKSB7fVxuICB9LFxuICBDdXN0b21FdmVudDogZnVuY3Rpb24gQ3VzdG9tRXZlbnQoKSB7XG4gICAgcmV0dXJuIHRoaXM7XG4gIH0sXG4gIGFkZEV2ZW50TGlzdGVuZXIoKSB7fSxcbiAgcmVtb3ZlRXZlbnRMaXN0ZW5lcigpIHt9LFxuICBnZXRDb21wdXRlZFN0eWxlKCkge1xuICAgIHJldHVybiB7XG4gICAgICBnZXRQcm9wZXJ0eVZhbHVlKCkge1xuICAgICAgICByZXR1cm4gJyc7XG4gICAgICB9XG4gICAgfTtcbiAgfSxcbiAgSW1hZ2UoKSB7fSxcbiAgRGF0ZSgpIHt9LFxuICBzY3JlZW46IHt9LFxuICBzZXRUaW1lb3V0KCkge30sXG4gIGNsZWFyVGltZW91dCgpIHt9LFxuICBtYXRjaE1lZGlhKCkge1xuICAgIHJldHVybiB7fTtcbiAgfSxcbiAgcmVxdWVzdEFuaW1hdGlvbkZyYW1lKGNhbGxiYWNrKSB7XG4gICAgaWYgKHR5cGVvZiBzZXRUaW1lb3V0ID09PSAndW5kZWZpbmVkJykge1xuICAgICAgY2FsbGJhY2soKTtcbiAgICAgIHJldHVybiBudWxsO1xuICAgIH1cbiAgICByZXR1cm4gc2V0VGltZW91dChjYWxsYmFjaywgMCk7XG4gIH0sXG4gIGNhbmNlbEFuaW1hdGlvbkZyYW1lKGlkKSB7XG4gICAgaWYgKHR5cGVvZiBzZXRUaW1lb3V0ID09PSAndW5kZWZpbmVkJykge1xuICAgICAgcmV0dXJuO1xuICAgIH1cbiAgICBjbGVhclRpbWVvdXQoaWQpO1xuICB9XG59O1xuZnVuY3Rpb24gZ2V0V2luZG93KCkge1xuICBjb25zdCB3aW4gPSB0eXBlb2Ygd2luZG93ICE9PSAndW5kZWZpbmVkJyA/IHdpbmRvdyA6IHt9O1xuICBleHRlbmQod2luLCBzc3JXaW5kb3cpO1xuICByZXR1cm4gd2luO1xufVxuXG5leHBvcnQgeyBnZXRXaW5kb3cgYXMgYSwgZ2V0RG9jdW1lbnQgYXMgZyB9O1xuIiwgImltcG9ydCB7IGEgYXMgZ2V0V2luZG93LCBnIGFzIGdldERvY3VtZW50IH0gZnJvbSAnLi9zc3Itd2luZG93LmVzbS5tanMnO1xuXG5mdW5jdGlvbiBjbGFzc2VzVG9Ub2tlbnMoY2xhc3Nlcykge1xuICBpZiAoY2xhc3NlcyA9PT0gdm9pZCAwKSB7XG4gICAgY2xhc3NlcyA9ICcnO1xuICB9XG4gIHJldHVybiBjbGFzc2VzLnRyaW0oKS5zcGxpdCgnICcpLmZpbHRlcihjID0+ICEhYy50cmltKCkpO1xufVxuXG5mdW5jdGlvbiBkZWxldGVQcm9wcyhvYmopIHtcbiAgY29uc3Qgb2JqZWN0ID0gb2JqO1xuICBPYmplY3Qua2V5cyhvYmplY3QpLmZvckVhY2goa2V5ID0+IHtcbiAgICB0cnkge1xuICAgICAgb2JqZWN0W2tleV0gPSBudWxsO1xuICAgIH0gY2F0Y2ggKGUpIHtcbiAgICAgIC8vIG5vIGdldHRlciBmb3Igb2JqZWN0XG4gICAgfVxuICAgIHRyeSB7XG4gICAgICBkZWxldGUgb2JqZWN0W2tleV07XG4gICAgfSBjYXRjaCAoZSkge1xuICAgICAgLy8gc29tZXRoaW5nIGdvdCB3cm9uZ1xuICAgIH1cbiAgfSk7XG59XG5mdW5jdGlvbiBuZXh0VGljayhjYWxsYmFjaywgZGVsYXkpIHtcbiAgaWYgKGRlbGF5ID09PSB2b2lkIDApIHtcbiAgICBkZWxheSA9IDA7XG4gIH1cbiAgcmV0dXJuIHNldFRpbWVvdXQoY2FsbGJhY2ssIGRlbGF5KTtcbn1cbmZ1bmN0aW9uIG5vdygpIHtcbiAgcmV0dXJuIERhdGUubm93KCk7XG59XG5mdW5jdGlvbiBnZXRDb21wdXRlZFN0eWxlKGVsKSB7XG4gIGNvbnN0IHdpbmRvdyA9IGdldFdpbmRvdygpO1xuICBsZXQgc3R5bGU7XG4gIGlmICh3aW5kb3cuZ2V0Q29tcHV0ZWRTdHlsZSkge1xuICAgIHN0eWxlID0gd2luZG93LmdldENvbXB1dGVkU3R5bGUoZWwsIG51bGwpO1xuICB9XG4gIGlmICghc3R5bGUgJiYgZWwuY3VycmVudFN0eWxlKSB7XG4gICAgc3R5bGUgPSBlbC5jdXJyZW50U3R5bGU7XG4gIH1cbiAgaWYgKCFzdHlsZSkge1xuICAgIHN0eWxlID0gZWwuc3R5bGU7XG4gIH1cbiAgcmV0dXJuIHN0eWxlO1xufVxuZnVuY3Rpb24gZ2V0VHJhbnNsYXRlKGVsLCBheGlzKSB7XG4gIGlmIChheGlzID09PSB2b2lkIDApIHtcbiAgICBheGlzID0gJ3gnO1xuICB9XG4gIGNvbnN0IHdpbmRvdyA9IGdldFdpbmRvdygpO1xuICBsZXQgbWF0cml4O1xuICBsZXQgY3VyVHJhbnNmb3JtO1xuICBsZXQgdHJhbnNmb3JtTWF0cml4O1xuICBjb25zdCBjdXJTdHlsZSA9IGdldENvbXB1dGVkU3R5bGUoZWwpO1xuICBpZiAod2luZG93LldlYktpdENTU01hdHJpeCkge1xuICAgIGN1clRyYW5zZm9ybSA9IGN1clN0eWxlLnRyYW5zZm9ybSB8fCBjdXJTdHlsZS53ZWJraXRUcmFuc2Zvcm07XG4gICAgaWYgKGN1clRyYW5zZm9ybS5zcGxpdCgnLCcpLmxlbmd0aCA+IDYpIHtcbiAgICAgIGN1clRyYW5zZm9ybSA9IGN1clRyYW5zZm9ybS5zcGxpdCgnLCAnKS5tYXAoYSA9PiBhLnJlcGxhY2UoJywnLCAnLicpKS5qb2luKCcsICcpO1xuICAgIH1cbiAgICAvLyBTb21lIG9sZCB2ZXJzaW9ucyBvZiBXZWJraXQgY2hva2Ugd2hlbiAnbm9uZScgaXMgcGFzc2VkOyBwYXNzXG4gICAgLy8gZW1wdHkgc3RyaW5nIGluc3RlYWQgaW4gdGhpcyBjYXNlXG4gICAgdHJhbnNmb3JtTWF0cml4ID0gbmV3IHdpbmRvdy5XZWJLaXRDU1NNYXRyaXgoY3VyVHJhbnNmb3JtID09PSAnbm9uZScgPyAnJyA6IGN1clRyYW5zZm9ybSk7XG4gIH0gZWxzZSB7XG4gICAgdHJhbnNmb3JtTWF0cml4ID0gY3VyU3R5bGUuTW96VHJhbnNmb3JtIHx8IGN1clN0eWxlLk9UcmFuc2Zvcm0gfHwgY3VyU3R5bGUuTXNUcmFuc2Zvcm0gfHwgY3VyU3R5bGUubXNUcmFuc2Zvcm0gfHwgY3VyU3R5bGUudHJhbnNmb3JtIHx8IGN1clN0eWxlLmdldFByb3BlcnR5VmFsdWUoJ3RyYW5zZm9ybScpLnJlcGxhY2UoJ3RyYW5zbGF0ZSgnLCAnbWF0cml4KDEsIDAsIDAsIDEsJyk7XG4gICAgbWF0cml4ID0gdHJhbnNmb3JtTWF0cml4LnRvU3RyaW5nKCkuc3BsaXQoJywnKTtcbiAgfVxuICBpZiAoYXhpcyA9PT0gJ3gnKSB7XG4gICAgLy8gTGF0ZXN0IENocm9tZSBhbmQgd2Via2l0cyBGaXhcbiAgICBpZiAod2luZG93LldlYktpdENTU01hdHJpeCkgY3VyVHJhbnNmb3JtID0gdHJhbnNmb3JtTWF0cml4Lm00MTtcbiAgICAvLyBDcmF6eSBJRTEwIE1hdHJpeFxuICAgIGVsc2UgaWYgKG1hdHJpeC5sZW5ndGggPT09IDE2KSBjdXJUcmFuc2Zvcm0gPSBwYXJzZUZsb2F0KG1hdHJpeFsxMl0pO1xuICAgIC8vIE5vcm1hbCBCcm93c2Vyc1xuICAgIGVsc2UgY3VyVHJhbnNmb3JtID0gcGFyc2VGbG9hdChtYXRyaXhbNF0pO1xuICB9XG4gIGlmIChheGlzID09PSAneScpIHtcbiAgICAvLyBMYXRlc3QgQ2hyb21lIGFuZCB3ZWJraXRzIEZpeFxuICAgIGlmICh3aW5kb3cuV2ViS2l0Q1NTTWF0cml4KSBjdXJUcmFuc2Zvcm0gPSB0cmFuc2Zvcm1NYXRyaXgubTQyO1xuICAgIC8vIENyYXp5IElFMTAgTWF0cml4XG4gICAgZWxzZSBpZiAobWF0cml4Lmxlbmd0aCA9PT0gMTYpIGN1clRyYW5zZm9ybSA9IHBhcnNlRmxvYXQobWF0cml4WzEzXSk7XG4gICAgLy8gTm9ybWFsIEJyb3dzZXJzXG4gICAgZWxzZSBjdXJUcmFuc2Zvcm0gPSBwYXJzZUZsb2F0KG1hdHJpeFs1XSk7XG4gIH1cbiAgcmV0dXJuIGN1clRyYW5zZm9ybSB8fCAwO1xufVxuZnVuY3Rpb24gaXNPYmplY3Qobykge1xuICByZXR1cm4gdHlwZW9mIG8gPT09ICdvYmplY3QnICYmIG8gIT09IG51bGwgJiYgby5jb25zdHJ1Y3RvciAmJiBPYmplY3QucHJvdG90eXBlLnRvU3RyaW5nLmNhbGwobykuc2xpY2UoOCwgLTEpID09PSAnT2JqZWN0Jztcbn1cbmZ1bmN0aW9uIGlzTm9kZShub2RlKSB7XG4gIC8vIGVzbGludC1kaXNhYmxlLW5leHQtbGluZVxuICBpZiAodHlwZW9mIHdpbmRvdyAhPT0gJ3VuZGVmaW5lZCcgJiYgdHlwZW9mIHdpbmRvdy5IVE1MRWxlbWVudCAhPT0gJ3VuZGVmaW5lZCcpIHtcbiAgICByZXR1cm4gbm9kZSBpbnN0YW5jZW9mIEhUTUxFbGVtZW50O1xuICB9XG4gIHJldHVybiBub2RlICYmIChub2RlLm5vZGVUeXBlID09PSAxIHx8IG5vZGUubm9kZVR5cGUgPT09IDExKTtcbn1cbmZ1bmN0aW9uIGV4dGVuZCgpIHtcbiAgY29uc3QgdG8gPSBPYmplY3QoYXJndW1lbnRzLmxlbmd0aCA8PSAwID8gdW5kZWZpbmVkIDogYXJndW1lbnRzWzBdKTtcbiAgY29uc3Qgbm9FeHRlbmQgPSBbJ19fcHJvdG9fXycsICdjb25zdHJ1Y3RvcicsICdwcm90b3R5cGUnXTtcbiAgZm9yIChsZXQgaSA9IDE7IGkgPCBhcmd1bWVudHMubGVuZ3RoOyBpICs9IDEpIHtcbiAgICBjb25zdCBuZXh0U291cmNlID0gaSA8IDAgfHwgYXJndW1lbnRzLmxlbmd0aCA8PSBpID8gdW5kZWZpbmVkIDogYXJndW1lbnRzW2ldO1xuICAgIGlmIChuZXh0U291cmNlICE9PSB1bmRlZmluZWQgJiYgbmV4dFNvdXJjZSAhPT0gbnVsbCAmJiAhaXNOb2RlKG5leHRTb3VyY2UpKSB7XG4gICAgICBjb25zdCBrZXlzQXJyYXkgPSBPYmplY3Qua2V5cyhPYmplY3QobmV4dFNvdXJjZSkpLmZpbHRlcihrZXkgPT4gbm9FeHRlbmQuaW5kZXhPZihrZXkpIDwgMCk7XG4gICAgICBmb3IgKGxldCBuZXh0SW5kZXggPSAwLCBsZW4gPSBrZXlzQXJyYXkubGVuZ3RoOyBuZXh0SW5kZXggPCBsZW47IG5leHRJbmRleCArPSAxKSB7XG4gICAgICAgIGNvbnN0IG5leHRLZXkgPSBrZXlzQXJyYXlbbmV4dEluZGV4XTtcbiAgICAgICAgY29uc3QgZGVzYyA9IE9iamVjdC5nZXRPd25Qcm9wZXJ0eURlc2NyaXB0b3IobmV4dFNvdXJjZSwgbmV4dEtleSk7XG4gICAgICAgIGlmIChkZXNjICE9PSB1bmRlZmluZWQgJiYgZGVzYy5lbnVtZXJhYmxlKSB7XG4gICAgICAgICAgaWYgKGlzT2JqZWN0KHRvW25leHRLZXldKSAmJiBpc09iamVjdChuZXh0U291cmNlW25leHRLZXldKSkge1xuICAgICAgICAgICAgaWYgKG5leHRTb3VyY2VbbmV4dEtleV0uX19zd2lwZXJfXykge1xuICAgICAgICAgICAgICB0b1tuZXh0S2V5XSA9IG5leHRTb3VyY2VbbmV4dEtleV07XG4gICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICBleHRlbmQodG9bbmV4dEtleV0sIG5leHRTb3VyY2VbbmV4dEtleV0pO1xuICAgICAgICAgICAgfVxuICAgICAgICAgIH0gZWxzZSBpZiAoIWlzT2JqZWN0KHRvW25leHRLZXldKSAmJiBpc09iamVjdChuZXh0U291cmNlW25leHRLZXldKSkge1xuICAgICAgICAgICAgdG9bbmV4dEtleV0gPSB7fTtcbiAgICAgICAgICAgIGlmIChuZXh0U291cmNlW25leHRLZXldLl9fc3dpcGVyX18pIHtcbiAgICAgICAgICAgICAgdG9bbmV4dEtleV0gPSBuZXh0U291cmNlW25leHRLZXldO1xuICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgZXh0ZW5kKHRvW25leHRLZXldLCBuZXh0U291cmNlW25leHRLZXldKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgdG9bbmV4dEtleV0gPSBuZXh0U291cmNlW25leHRLZXldO1xuICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgICAgfVxuICAgIH1cbiAgfVxuICByZXR1cm4gdG87XG59XG5mdW5jdGlvbiBzZXRDU1NQcm9wZXJ0eShlbCwgdmFyTmFtZSwgdmFyVmFsdWUpIHtcbiAgZWwuc3R5bGUuc2V0UHJvcGVydHkodmFyTmFtZSwgdmFyVmFsdWUpO1xufVxuZnVuY3Rpb24gYW5pbWF0ZUNTU01vZGVTY3JvbGwoX3JlZikge1xuICBsZXQge1xuICAgIHN3aXBlcixcbiAgICB0YXJnZXRQb3NpdGlvbixcbiAgICBzaWRlXG4gIH0gPSBfcmVmO1xuICBjb25zdCB3aW5kb3cgPSBnZXRXaW5kb3coKTtcbiAgY29uc3Qgc3RhcnRQb3NpdGlvbiA9IC1zd2lwZXIudHJhbnNsYXRlO1xuICBsZXQgc3RhcnRUaW1lID0gbnVsbDtcbiAgbGV0IHRpbWU7XG4gIGNvbnN0IGR1cmF0aW9uID0gc3dpcGVyLnBhcmFtcy5zcGVlZDtcbiAgc3dpcGVyLndyYXBwZXJFbC5zdHlsZS5zY3JvbGxTbmFwVHlwZSA9ICdub25lJztcbiAgd2luZG93LmNhbmNlbEFuaW1hdGlvbkZyYW1lKHN3aXBlci5jc3NNb2RlRnJhbWVJRCk7XG4gIGNvbnN0IGRpciA9IHRhcmdldFBvc2l0aW9uID4gc3RhcnRQb3NpdGlvbiA/ICduZXh0JyA6ICdwcmV2JztcbiAgY29uc3QgaXNPdXRPZkJvdW5kID0gKGN1cnJlbnQsIHRhcmdldCkgPT4ge1xuICAgIHJldHVybiBkaXIgPT09ICduZXh0JyAmJiBjdXJyZW50ID49IHRhcmdldCB8fCBkaXIgPT09ICdwcmV2JyAmJiBjdXJyZW50IDw9IHRhcmdldDtcbiAgfTtcbiAgY29uc3QgYW5pbWF0ZSA9ICgpID0+IHtcbiAgICB0aW1lID0gbmV3IERhdGUoKS5nZXRUaW1lKCk7XG4gICAgaWYgKHN0YXJ0VGltZSA9PT0gbnVsbCkge1xuICAgICAgc3RhcnRUaW1lID0gdGltZTtcbiAgICB9XG4gICAgY29uc3QgcHJvZ3Jlc3MgPSBNYXRoLm1heChNYXRoLm1pbigodGltZSAtIHN0YXJ0VGltZSkgLyBkdXJhdGlvbiwgMSksIDApO1xuICAgIGNvbnN0IGVhc2VQcm9ncmVzcyA9IDAuNSAtIE1hdGguY29zKHByb2dyZXNzICogTWF0aC5QSSkgLyAyO1xuICAgIGxldCBjdXJyZW50UG9zaXRpb24gPSBzdGFydFBvc2l0aW9uICsgZWFzZVByb2dyZXNzICogKHRhcmdldFBvc2l0aW9uIC0gc3RhcnRQb3NpdGlvbik7XG4gICAgaWYgKGlzT3V0T2ZCb3VuZChjdXJyZW50UG9zaXRpb24sIHRhcmdldFBvc2l0aW9uKSkge1xuICAgICAgY3VycmVudFBvc2l0aW9uID0gdGFyZ2V0UG9zaXRpb247XG4gICAgfVxuICAgIHN3aXBlci53cmFwcGVyRWwuc2Nyb2xsVG8oe1xuICAgICAgW3NpZGVdOiBjdXJyZW50UG9zaXRpb25cbiAgICB9KTtcbiAgICBpZiAoaXNPdXRPZkJvdW5kKGN1cnJlbnRQb3NpdGlvbiwgdGFyZ2V0UG9zaXRpb24pKSB7XG4gICAgICBzd2lwZXIud3JhcHBlckVsLnN0eWxlLm92ZXJmbG93ID0gJ2hpZGRlbic7XG4gICAgICBzd2lwZXIud3JhcHBlckVsLnN0eWxlLnNjcm9sbFNuYXBUeXBlID0gJyc7XG4gICAgICBzZXRUaW1lb3V0KCgpID0+IHtcbiAgICAgICAgc3dpcGVyLndyYXBwZXJFbC5zdHlsZS5vdmVyZmxvdyA9ICcnO1xuICAgICAgICBzd2lwZXIud3JhcHBlckVsLnNjcm9sbFRvKHtcbiAgICAgICAgICBbc2lkZV06IGN1cnJlbnRQb3NpdGlvblxuICAgICAgICB9KTtcbiAgICAgIH0pO1xuICAgICAgd2luZG93LmNhbmNlbEFuaW1hdGlvbkZyYW1lKHN3aXBlci5jc3NNb2RlRnJhbWVJRCk7XG4gICAgICByZXR1cm47XG4gICAgfVxuICAgIHN3aXBlci5jc3NNb2RlRnJhbWVJRCA9IHdpbmRvdy5yZXF1ZXN0QW5pbWF0aW9uRnJhbWUoYW5pbWF0ZSk7XG4gIH07XG4gIGFuaW1hdGUoKTtcbn1cbmZ1bmN0aW9uIGdldFNsaWRlVHJhbnNmb3JtRWwoc2xpZGVFbCkge1xuICByZXR1cm4gc2xpZGVFbC5xdWVyeVNlbGVjdG9yKCcuc3dpcGVyLXNsaWRlLXRyYW5zZm9ybScpIHx8IHNsaWRlRWwuc2hhZG93Um9vdCAmJiBzbGlkZUVsLnNoYWRvd1Jvb3QucXVlcnlTZWxlY3RvcignLnN3aXBlci1zbGlkZS10cmFuc2Zvcm0nKSB8fCBzbGlkZUVsO1xufVxuZnVuY3Rpb24gZWxlbWVudENoaWxkcmVuKGVsZW1lbnQsIHNlbGVjdG9yKSB7XG4gIGlmIChzZWxlY3RvciA9PT0gdm9pZCAwKSB7XG4gICAgc2VsZWN0b3IgPSAnJztcbiAgfVxuICBjb25zdCBjaGlsZHJlbiA9IFsuLi5lbGVtZW50LmNoaWxkcmVuXTtcbiAgaWYgKGVsZW1lbnQgaW5zdGFuY2VvZiBIVE1MU2xvdEVsZW1lbnQpIHtcbiAgICBjaGlsZHJlbi5wdXNoKC4uLmVsZW1lbnQuYXNzaWduZWRFbGVtZW50cygpKTtcbiAgfVxuICBpZiAoIXNlbGVjdG9yKSB7XG4gICAgcmV0dXJuIGNoaWxkcmVuO1xuICB9XG4gIHJldHVybiBjaGlsZHJlbi5maWx0ZXIoZWwgPT4gZWwubWF0Y2hlcyhzZWxlY3RvcikpO1xufVxuZnVuY3Rpb24gZWxlbWVudElzQ2hpbGRPZihlbCwgcGFyZW50KSB7XG4gIGNvbnN0IGlzQ2hpbGQgPSBwYXJlbnQuY29udGFpbnMoZWwpO1xuICBpZiAoIWlzQ2hpbGQgJiYgcGFyZW50IGluc3RhbmNlb2YgSFRNTFNsb3RFbGVtZW50KSB7XG4gICAgY29uc3QgY2hpbGRyZW4gPSBbLi4ucGFyZW50LmFzc2lnbmVkRWxlbWVudHMoKV07XG4gICAgcmV0dXJuIGNoaWxkcmVuLmluY2x1ZGVzKGVsKTtcbiAgfVxuICByZXR1cm4gaXNDaGlsZDtcbn1cbmZ1bmN0aW9uIHNob3dXYXJuaW5nKHRleHQpIHtcbiAgdHJ5IHtcbiAgICBjb25zb2xlLndhcm4odGV4dCk7XG4gICAgcmV0dXJuO1xuICB9IGNhdGNoIChlcnIpIHtcbiAgICAvLyBlcnJcbiAgfVxufVxuZnVuY3Rpb24gY3JlYXRlRWxlbWVudCh0YWcsIGNsYXNzZXMpIHtcbiAgaWYgKGNsYXNzZXMgPT09IHZvaWQgMCkge1xuICAgIGNsYXNzZXMgPSBbXTtcbiAgfVxuICBjb25zdCBlbCA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQodGFnKTtcbiAgZWwuY2xhc3NMaXN0LmFkZCguLi4oQXJyYXkuaXNBcnJheShjbGFzc2VzKSA/IGNsYXNzZXMgOiBjbGFzc2VzVG9Ub2tlbnMoY2xhc3NlcykpKTtcbiAgcmV0dXJuIGVsO1xufVxuZnVuY3Rpb24gZWxlbWVudE9mZnNldChlbCkge1xuICBjb25zdCB3aW5kb3cgPSBnZXRXaW5kb3coKTtcbiAgY29uc3QgZG9jdW1lbnQgPSBnZXREb2N1bWVudCgpO1xuICBjb25zdCBib3ggPSBlbC5nZXRCb3VuZGluZ0NsaWVudFJlY3QoKTtcbiAgY29uc3QgYm9keSA9IGRvY3VtZW50LmJvZHk7XG4gIGNvbnN0IGNsaWVudFRvcCA9IGVsLmNsaWVudFRvcCB8fCBib2R5LmNsaWVudFRvcCB8fCAwO1xuICBjb25zdCBjbGllbnRMZWZ0ID0gZWwuY2xpZW50TGVmdCB8fCBib2R5LmNsaWVudExlZnQgfHwgMDtcbiAgY29uc3Qgc2Nyb2xsVG9wID0gZWwgPT09IHdpbmRvdyA/IHdpbmRvdy5zY3JvbGxZIDogZWwuc2Nyb2xsVG9wO1xuICBjb25zdCBzY3JvbGxMZWZ0ID0gZWwgPT09IHdpbmRvdyA/IHdpbmRvdy5zY3JvbGxYIDogZWwuc2Nyb2xsTGVmdDtcbiAgcmV0dXJuIHtcbiAgICB0b3A6IGJveC50b3AgKyBzY3JvbGxUb3AgLSBjbGllbnRUb3AsXG4gICAgbGVmdDogYm94LmxlZnQgKyBzY3JvbGxMZWZ0IC0gY2xpZW50TGVmdFxuICB9O1xufVxuZnVuY3Rpb24gZWxlbWVudFByZXZBbGwoZWwsIHNlbGVjdG9yKSB7XG4gIGNvbnN0IHByZXZFbHMgPSBbXTtcbiAgd2hpbGUgKGVsLnByZXZpb3VzRWxlbWVudFNpYmxpbmcpIHtcbiAgICBjb25zdCBwcmV2ID0gZWwucHJldmlvdXNFbGVtZW50U2libGluZzsgLy8gZXNsaW50LWRpc2FibGUtbGluZVxuICAgIGlmIChzZWxlY3Rvcikge1xuICAgICAgaWYgKHByZXYubWF0Y2hlcyhzZWxlY3RvcikpIHByZXZFbHMucHVzaChwcmV2KTtcbiAgICB9IGVsc2UgcHJldkVscy5wdXNoKHByZXYpO1xuICAgIGVsID0gcHJldjtcbiAgfVxuICByZXR1cm4gcHJldkVscztcbn1cbmZ1bmN0aW9uIGVsZW1lbnROZXh0QWxsKGVsLCBzZWxlY3Rvcikge1xuICBjb25zdCBuZXh0RWxzID0gW107XG4gIHdoaWxlIChlbC5uZXh0RWxlbWVudFNpYmxpbmcpIHtcbiAgICBjb25zdCBuZXh0ID0gZWwubmV4dEVsZW1lbnRTaWJsaW5nOyAvLyBlc2xpbnQtZGlzYWJsZS1saW5lXG4gICAgaWYgKHNlbGVjdG9yKSB7XG4gICAgICBpZiAobmV4dC5tYXRjaGVzKHNlbGVjdG9yKSkgbmV4dEVscy5wdXNoKG5leHQpO1xuICAgIH0gZWxzZSBuZXh0RWxzLnB1c2gobmV4dCk7XG4gICAgZWwgPSBuZXh0O1xuICB9XG4gIHJldHVybiBuZXh0RWxzO1xufVxuZnVuY3Rpb24gZWxlbWVudFN0eWxlKGVsLCBwcm9wKSB7XG4gIGNvbnN0IHdpbmRvdyA9IGdldFdpbmRvdygpO1xuICByZXR1cm4gd2luZG93LmdldENvbXB1dGVkU3R5bGUoZWwsIG51bGwpLmdldFByb3BlcnR5VmFsdWUocHJvcCk7XG59XG5mdW5jdGlvbiBlbGVtZW50SW5kZXgoZWwpIHtcbiAgbGV0IGNoaWxkID0gZWw7XG4gIGxldCBpO1xuICBpZiAoY2hpbGQpIHtcbiAgICBpID0gMDtcbiAgICAvLyBlc2xpbnQtZGlzYWJsZS1uZXh0LWxpbmVcbiAgICB3aGlsZSAoKGNoaWxkID0gY2hpbGQucHJldmlvdXNTaWJsaW5nKSAhPT0gbnVsbCkge1xuICAgICAgaWYgKGNoaWxkLm5vZGVUeXBlID09PSAxKSBpICs9IDE7XG4gICAgfVxuICAgIHJldHVybiBpO1xuICB9XG4gIHJldHVybiB1bmRlZmluZWQ7XG59XG5mdW5jdGlvbiBlbGVtZW50UGFyZW50cyhlbCwgc2VsZWN0b3IpIHtcbiAgY29uc3QgcGFyZW50cyA9IFtdOyAvLyBlc2xpbnQtZGlzYWJsZS1saW5lXG4gIGxldCBwYXJlbnQgPSBlbC5wYXJlbnRFbGVtZW50OyAvLyBlc2xpbnQtZGlzYWJsZS1saW5lXG4gIHdoaWxlIChwYXJlbnQpIHtcbiAgICBpZiAoc2VsZWN0b3IpIHtcbiAgICAgIGlmIChwYXJlbnQubWF0Y2hlcyhzZWxlY3RvcikpIHBhcmVudHMucHVzaChwYXJlbnQpO1xuICAgIH0gZWxzZSB7XG4gICAgICBwYXJlbnRzLnB1c2gocGFyZW50KTtcbiAgICB9XG4gICAgcGFyZW50ID0gcGFyZW50LnBhcmVudEVsZW1lbnQ7XG4gIH1cbiAgcmV0dXJuIHBhcmVudHM7XG59XG5mdW5jdGlvbiBlbGVtZW50VHJhbnNpdGlvbkVuZChlbCwgY2FsbGJhY2spIHtcbiAgZnVuY3Rpb24gZmlyZUNhbGxCYWNrKGUpIHtcbiAgICBpZiAoZS50YXJnZXQgIT09IGVsKSByZXR1cm47XG4gICAgY2FsbGJhY2suY2FsbChlbCwgZSk7XG4gICAgZWwucmVtb3ZlRXZlbnRMaXN0ZW5lcigndHJhbnNpdGlvbmVuZCcsIGZpcmVDYWxsQmFjayk7XG4gIH1cbiAgaWYgKGNhbGxiYWNrKSB7XG4gICAgZWwuYWRkRXZlbnRMaXN0ZW5lcigndHJhbnNpdGlvbmVuZCcsIGZpcmVDYWxsQmFjayk7XG4gIH1cbn1cbmZ1bmN0aW9uIGVsZW1lbnRPdXRlclNpemUoZWwsIHNpemUsIGluY2x1ZGVNYXJnaW5zKSB7XG4gIGNvbnN0IHdpbmRvdyA9IGdldFdpbmRvdygpO1xuICBpZiAoaW5jbHVkZU1hcmdpbnMpIHtcbiAgICByZXR1cm4gZWxbc2l6ZSA9PT0gJ3dpZHRoJyA/ICdvZmZzZXRXaWR0aCcgOiAnb2Zmc2V0SGVpZ2h0J10gKyBwYXJzZUZsb2F0KHdpbmRvdy5nZXRDb21wdXRlZFN0eWxlKGVsLCBudWxsKS5nZXRQcm9wZXJ0eVZhbHVlKHNpemUgPT09ICd3aWR0aCcgPyAnbWFyZ2luLXJpZ2h0JyA6ICdtYXJnaW4tdG9wJykpICsgcGFyc2VGbG9hdCh3aW5kb3cuZ2V0Q29tcHV0ZWRTdHlsZShlbCwgbnVsbCkuZ2V0UHJvcGVydHlWYWx1ZShzaXplID09PSAnd2lkdGgnID8gJ21hcmdpbi1sZWZ0JyA6ICdtYXJnaW4tYm90dG9tJykpO1xuICB9XG4gIHJldHVybiBlbC5vZmZzZXRXaWR0aDtcbn1cbmZ1bmN0aW9uIG1ha2VFbGVtZW50c0FycmF5KGVsKSB7XG4gIHJldHVybiAoQXJyYXkuaXNBcnJheShlbCkgPyBlbCA6IFtlbF0pLmZpbHRlcihlID0+ICEhZSk7XG59XG5mdW5jdGlvbiBnZXRSb3RhdGVGaXgoc3dpcGVyKSB7XG4gIHJldHVybiB2ID0+IHtcbiAgICBpZiAoTWF0aC5hYnModikgPiAwICYmIHN3aXBlci5icm93c2VyICYmIHN3aXBlci5icm93c2VyLm5lZWQzZEZpeCAmJiBNYXRoLmFicyh2KSAlIDkwID09PSAwKSB7XG4gICAgICByZXR1cm4gdiArIDAuMDAxO1xuICAgIH1cbiAgICByZXR1cm4gdjtcbiAgfTtcbn1cblxuZXhwb3J0IHsgZWxlbWVudFBhcmVudHMgYXMgYSwgZWxlbWVudE9mZnNldCBhcyBiLCBjcmVhdGVFbGVtZW50IGFzIGMsIG5vdyBhcyBkLCBlbGVtZW50Q2hpbGRyZW4gYXMgZSwgZWxlbWVudE91dGVyU2l6ZSBhcyBmLCBnZXRTbGlkZVRyYW5zZm9ybUVsIGFzIGcsIGVsZW1lbnRJbmRleCBhcyBoLCBjbGFzc2VzVG9Ub2tlbnMgYXMgaSwgZ2V0VHJhbnNsYXRlIGFzIGosIGVsZW1lbnRUcmFuc2l0aW9uRW5kIGFzIGssIGlzT2JqZWN0IGFzIGwsIG1ha2VFbGVtZW50c0FycmF5IGFzIG0sIG5leHRUaWNrIGFzIG4sIGdldFJvdGF0ZUZpeCBhcyBvLCBlbGVtZW50U3R5bGUgYXMgcCwgZWxlbWVudE5leHRBbGwgYXMgcSwgZWxlbWVudFByZXZBbGwgYXMgciwgc2V0Q1NTUHJvcGVydHkgYXMgcywgYW5pbWF0ZUNTU01vZGVTY3JvbGwgYXMgdCwgc2hvd1dhcm5pbmcgYXMgdSwgZWxlbWVudElzQ2hpbGRPZiBhcyB2LCBleHRlbmQgYXMgdywgZGVsZXRlUHJvcHMgYXMgeCB9O1xuIiwgImltcG9ydCB7IGEgYXMgZ2V0V2luZG93LCBnIGFzIGdldERvY3VtZW50IH0gZnJvbSAnLi9zc3Itd2luZG93LmVzbS5tanMnO1xuaW1wb3J0IHsgYSBhcyBlbGVtZW50UGFyZW50cywgcCBhcyBlbGVtZW50U3R5bGUsIGUgYXMgZWxlbWVudENoaWxkcmVuLCBzIGFzIHNldENTU1Byb3BlcnR5LCBmIGFzIGVsZW1lbnRPdXRlclNpemUsIHEgYXMgZWxlbWVudE5leHRBbGwsIHIgYXMgZWxlbWVudFByZXZBbGwsIGogYXMgZ2V0VHJhbnNsYXRlLCB0IGFzIGFuaW1hdGVDU1NNb2RlU2Nyb2xsLCBuIGFzIG5leHRUaWNrLCB1IGFzIHNob3dXYXJuaW5nLCBjIGFzIGNyZWF0ZUVsZW1lbnQsIHYgYXMgZWxlbWVudElzQ2hpbGRPZiwgZCBhcyBub3csIHcgYXMgZXh0ZW5kLCBoIGFzIGVsZW1lbnRJbmRleCwgeCBhcyBkZWxldGVQcm9wcyB9IGZyb20gJy4vdXRpbHMubWpzJztcblxubGV0IHN1cHBvcnQ7XG5mdW5jdGlvbiBjYWxjU3VwcG9ydCgpIHtcbiAgY29uc3Qgd2luZG93ID0gZ2V0V2luZG93KCk7XG4gIGNvbnN0IGRvY3VtZW50ID0gZ2V0RG9jdW1lbnQoKTtcbiAgcmV0dXJuIHtcbiAgICBzbW9vdGhTY3JvbGw6IGRvY3VtZW50LmRvY3VtZW50RWxlbWVudCAmJiBkb2N1bWVudC5kb2N1bWVudEVsZW1lbnQuc3R5bGUgJiYgJ3Njcm9sbEJlaGF2aW9yJyBpbiBkb2N1bWVudC5kb2N1bWVudEVsZW1lbnQuc3R5bGUsXG4gICAgdG91Y2g6ICEhKCdvbnRvdWNoc3RhcnQnIGluIHdpbmRvdyB8fCB3aW5kb3cuRG9jdW1lbnRUb3VjaCAmJiBkb2N1bWVudCBpbnN0YW5jZW9mIHdpbmRvdy5Eb2N1bWVudFRvdWNoKVxuICB9O1xufVxuZnVuY3Rpb24gZ2V0U3VwcG9ydCgpIHtcbiAgaWYgKCFzdXBwb3J0KSB7XG4gICAgc3VwcG9ydCA9IGNhbGNTdXBwb3J0KCk7XG4gIH1cbiAgcmV0dXJuIHN1cHBvcnQ7XG59XG5cbmxldCBkZXZpY2VDYWNoZWQ7XG5mdW5jdGlvbiBjYWxjRGV2aWNlKF90ZW1wKSB7XG4gIGxldCB7XG4gICAgdXNlckFnZW50XG4gIH0gPSBfdGVtcCA9PT0gdm9pZCAwID8ge30gOiBfdGVtcDtcbiAgY29uc3Qgc3VwcG9ydCA9IGdldFN1cHBvcnQoKTtcbiAgY29uc3Qgd2luZG93ID0gZ2V0V2luZG93KCk7XG4gIGNvbnN0IHBsYXRmb3JtID0gd2luZG93Lm5hdmlnYXRvci5wbGF0Zm9ybTtcbiAgY29uc3QgdWEgPSB1c2VyQWdlbnQgfHwgd2luZG93Lm5hdmlnYXRvci51c2VyQWdlbnQ7XG4gIGNvbnN0IGRldmljZSA9IHtcbiAgICBpb3M6IGZhbHNlLFxuICAgIGFuZHJvaWQ6IGZhbHNlXG4gIH07XG4gIGNvbnN0IHNjcmVlbldpZHRoID0gd2luZG93LnNjcmVlbi53aWR0aDtcbiAgY29uc3Qgc2NyZWVuSGVpZ2h0ID0gd2luZG93LnNjcmVlbi5oZWlnaHQ7XG4gIGNvbnN0IGFuZHJvaWQgPSB1YS5tYXRjaCgvKEFuZHJvaWQpOz9bXFxzXFwvXSsoW1xcZC5dKyk/Lyk7IC8vIGVzbGludC1kaXNhYmxlLWxpbmVcbiAgbGV0IGlwYWQgPSB1YS5tYXRjaCgvKGlQYWQpLipPU1xccyhbXFxkX10rKS8pO1xuICBjb25zdCBpcG9kID0gdWEubWF0Y2goLyhpUG9kKSguKk9TXFxzKFtcXGRfXSspKT8vKTtcbiAgY29uc3QgaXBob25lID0gIWlwYWQgJiYgdWEubWF0Y2goLyhpUGhvbmVcXHNPU3xpT1MpXFxzKFtcXGRfXSspLyk7XG4gIGNvbnN0IHdpbmRvd3MgPSBwbGF0Zm9ybSA9PT0gJ1dpbjMyJztcbiAgbGV0IG1hY29zID0gcGxhdGZvcm0gPT09ICdNYWNJbnRlbCc7XG5cbiAgLy8gaVBhZE9zIDEzIGZpeFxuICBjb25zdCBpUGFkU2NyZWVucyA9IFsnMTAyNHgxMzY2JywgJzEzNjZ4MTAyNCcsICc4MzR4MTE5NCcsICcxMTk0eDgzNCcsICc4MzR4MTExMicsICcxMTEyeDgzNCcsICc3Njh4MTAyNCcsICcxMDI0eDc2OCcsICc4MjB4MTE4MCcsICcxMTgweDgyMCcsICc4MTB4MTA4MCcsICcxMDgweDgxMCddO1xuICBpZiAoIWlwYWQgJiYgbWFjb3MgJiYgc3VwcG9ydC50b3VjaCAmJiBpUGFkU2NyZWVucy5pbmRleE9mKGAke3NjcmVlbldpZHRofXgke3NjcmVlbkhlaWdodH1gKSA+PSAwKSB7XG4gICAgaXBhZCA9IHVhLm1hdGNoKC8oVmVyc2lvbilcXC8oW1xcZC5dKykvKTtcbiAgICBpZiAoIWlwYWQpIGlwYWQgPSBbMCwgMSwgJzEzXzBfMCddO1xuICAgIG1hY29zID0gZmFsc2U7XG4gIH1cblxuICAvLyBBbmRyb2lkXG4gIGlmIChhbmRyb2lkICYmICF3aW5kb3dzKSB7XG4gICAgZGV2aWNlLm9zID0gJ2FuZHJvaWQnO1xuICAgIGRldmljZS5hbmRyb2lkID0gdHJ1ZTtcbiAgfVxuICBpZiAoaXBhZCB8fCBpcGhvbmUgfHwgaXBvZCkge1xuICAgIGRldmljZS5vcyA9ICdpb3MnO1xuICAgIGRldmljZS5pb3MgPSB0cnVlO1xuICB9XG5cbiAgLy8gRXhwb3J0IG9iamVjdFxuICByZXR1cm4gZGV2aWNlO1xufVxuZnVuY3Rpb24gZ2V0RGV2aWNlKG92ZXJyaWRlcykge1xuICBpZiAob3ZlcnJpZGVzID09PSB2b2lkIDApIHtcbiAgICBvdmVycmlkZXMgPSB7fTtcbiAgfVxuICBpZiAoIWRldmljZUNhY2hlZCkge1xuICAgIGRldmljZUNhY2hlZCA9IGNhbGNEZXZpY2Uob3ZlcnJpZGVzKTtcbiAgfVxuICByZXR1cm4gZGV2aWNlQ2FjaGVkO1xufVxuXG5sZXQgYnJvd3NlcjtcbmZ1bmN0aW9uIGNhbGNCcm93c2VyKCkge1xuICBjb25zdCB3aW5kb3cgPSBnZXRXaW5kb3coKTtcbiAgY29uc3QgZGV2aWNlID0gZ2V0RGV2aWNlKCk7XG4gIGxldCBuZWVkUGVyc3BlY3RpdmVGaXggPSBmYWxzZTtcbiAgZnVuY3Rpb24gaXNTYWZhcmkoKSB7XG4gICAgY29uc3QgdWEgPSB3aW5kb3cubmF2aWdhdG9yLnVzZXJBZ2VudC50b0xvd2VyQ2FzZSgpO1xuICAgIHJldHVybiB1YS5pbmRleE9mKCdzYWZhcmknKSA+PSAwICYmIHVhLmluZGV4T2YoJ2Nocm9tZScpIDwgMCAmJiB1YS5pbmRleE9mKCdhbmRyb2lkJykgPCAwO1xuICB9XG4gIGlmIChpc1NhZmFyaSgpKSB7XG4gICAgY29uc3QgdWEgPSBTdHJpbmcod2luZG93Lm5hdmlnYXRvci51c2VyQWdlbnQpO1xuICAgIGlmICh1YS5pbmNsdWRlcygnVmVyc2lvbi8nKSkge1xuICAgICAgY29uc3QgW21ham9yLCBtaW5vcl0gPSB1YS5zcGxpdCgnVmVyc2lvbi8nKVsxXS5zcGxpdCgnICcpWzBdLnNwbGl0KCcuJykubWFwKG51bSA9PiBOdW1iZXIobnVtKSk7XG4gICAgICBuZWVkUGVyc3BlY3RpdmVGaXggPSBtYWpvciA8IDE2IHx8IG1ham9yID09PSAxNiAmJiBtaW5vciA8IDI7XG4gICAgfVxuICB9XG4gIGNvbnN0IGlzV2ViVmlldyA9IC8oaVBob25lfGlQb2R8aVBhZCkuKkFwcGxlV2ViS2l0KD8hLipTYWZhcmkpL2kudGVzdCh3aW5kb3cubmF2aWdhdG9yLnVzZXJBZ2VudCk7XG4gIGNvbnN0IGlzU2FmYXJpQnJvd3NlciA9IGlzU2FmYXJpKCk7XG4gIGNvbnN0IG5lZWQzZEZpeCA9IGlzU2FmYXJpQnJvd3NlciB8fCBpc1dlYlZpZXcgJiYgZGV2aWNlLmlvcztcbiAgcmV0dXJuIHtcbiAgICBpc1NhZmFyaTogbmVlZFBlcnNwZWN0aXZlRml4IHx8IGlzU2FmYXJpQnJvd3NlcixcbiAgICBuZWVkUGVyc3BlY3RpdmVGaXgsXG4gICAgbmVlZDNkRml4LFxuICAgIGlzV2ViVmlld1xuICB9O1xufVxuZnVuY3Rpb24gZ2V0QnJvd3NlcigpIHtcbiAgaWYgKCFicm93c2VyKSB7XG4gICAgYnJvd3NlciA9IGNhbGNCcm93c2VyKCk7XG4gIH1cbiAgcmV0dXJuIGJyb3dzZXI7XG59XG5cbmZ1bmN0aW9uIFJlc2l6ZShfcmVmKSB7XG4gIGxldCB7XG4gICAgc3dpcGVyLFxuICAgIG9uLFxuICAgIGVtaXRcbiAgfSA9IF9yZWY7XG4gIGNvbnN0IHdpbmRvdyA9IGdldFdpbmRvdygpO1xuICBsZXQgb2JzZXJ2ZXIgPSBudWxsO1xuICBsZXQgYW5pbWF0aW9uRnJhbWUgPSBudWxsO1xuICBjb25zdCByZXNpemVIYW5kbGVyID0gKCkgPT4ge1xuICAgIGlmICghc3dpcGVyIHx8IHN3aXBlci5kZXN0cm95ZWQgfHwgIXN3aXBlci5pbml0aWFsaXplZCkgcmV0dXJuO1xuICAgIGVtaXQoJ2JlZm9yZVJlc2l6ZScpO1xuICAgIGVtaXQoJ3Jlc2l6ZScpO1xuICB9O1xuICBjb25zdCBjcmVhdGVPYnNlcnZlciA9ICgpID0+IHtcbiAgICBpZiAoIXN3aXBlciB8fCBzd2lwZXIuZGVzdHJveWVkIHx8ICFzd2lwZXIuaW5pdGlhbGl6ZWQpIHJldHVybjtcbiAgICBvYnNlcnZlciA9IG5ldyBSZXNpemVPYnNlcnZlcihlbnRyaWVzID0+IHtcbiAgICAgIGFuaW1hdGlvbkZyYW1lID0gd2luZG93LnJlcXVlc3RBbmltYXRpb25GcmFtZSgoKSA9PiB7XG4gICAgICAgIGNvbnN0IHtcbiAgICAgICAgICB3aWR0aCxcbiAgICAgICAgICBoZWlnaHRcbiAgICAgICAgfSA9IHN3aXBlcjtcbiAgICAgICAgbGV0IG5ld1dpZHRoID0gd2lkdGg7XG4gICAgICAgIGxldCBuZXdIZWlnaHQgPSBoZWlnaHQ7XG4gICAgICAgIGVudHJpZXMuZm9yRWFjaChfcmVmMiA9PiB7XG4gICAgICAgICAgbGV0IHtcbiAgICAgICAgICAgIGNvbnRlbnRCb3hTaXplLFxuICAgICAgICAgICAgY29udGVudFJlY3QsXG4gICAgICAgICAgICB0YXJnZXRcbiAgICAgICAgICB9ID0gX3JlZjI7XG4gICAgICAgICAgaWYgKHRhcmdldCAmJiB0YXJnZXQgIT09IHN3aXBlci5lbCkgcmV0dXJuO1xuICAgICAgICAgIG5ld1dpZHRoID0gY29udGVudFJlY3QgPyBjb250ZW50UmVjdC53aWR0aCA6IChjb250ZW50Qm94U2l6ZVswXSB8fCBjb250ZW50Qm94U2l6ZSkuaW5saW5lU2l6ZTtcbiAgICAgICAgICBuZXdIZWlnaHQgPSBjb250ZW50UmVjdCA/IGNvbnRlbnRSZWN0LmhlaWdodCA6IChjb250ZW50Qm94U2l6ZVswXSB8fCBjb250ZW50Qm94U2l6ZSkuYmxvY2tTaXplO1xuICAgICAgICB9KTtcbiAgICAgICAgaWYgKG5ld1dpZHRoICE9PSB3aWR0aCB8fCBuZXdIZWlnaHQgIT09IGhlaWdodCkge1xuICAgICAgICAgIHJlc2l6ZUhhbmRsZXIoKTtcbiAgICAgICAgfVxuICAgICAgfSk7XG4gICAgfSk7XG4gICAgb2JzZXJ2ZXIub2JzZXJ2ZShzd2lwZXIuZWwpO1xuICB9O1xuICBjb25zdCByZW1vdmVPYnNlcnZlciA9ICgpID0+IHtcbiAgICBpZiAoYW5pbWF0aW9uRnJhbWUpIHtcbiAgICAgIHdpbmRvdy5jYW5jZWxBbmltYXRpb25GcmFtZShhbmltYXRpb25GcmFtZSk7XG4gICAgfVxuICAgIGlmIChvYnNlcnZlciAmJiBvYnNlcnZlci51bm9ic2VydmUgJiYgc3dpcGVyLmVsKSB7XG4gICAgICBvYnNlcnZlci51bm9ic2VydmUoc3dpcGVyLmVsKTtcbiAgICAgIG9ic2VydmVyID0gbnVsbDtcbiAgICB9XG4gIH07XG4gIGNvbnN0IG9yaWVudGF0aW9uQ2hhbmdlSGFuZGxlciA9ICgpID0+IHtcbiAgICBpZiAoIXN3aXBlciB8fCBzd2lwZXIuZGVzdHJveWVkIHx8ICFzd2lwZXIuaW5pdGlhbGl6ZWQpIHJldHVybjtcbiAgICBlbWl0KCdvcmllbnRhdGlvbmNoYW5nZScpO1xuICB9O1xuICBvbignaW5pdCcsICgpID0+IHtcbiAgICBpZiAoc3dpcGVyLnBhcmFtcy5yZXNpemVPYnNlcnZlciAmJiB0eXBlb2Ygd2luZG93LlJlc2l6ZU9ic2VydmVyICE9PSAndW5kZWZpbmVkJykge1xuICAgICAgY3JlYXRlT2JzZXJ2ZXIoKTtcbiAgICAgIHJldHVybjtcbiAgICB9XG4gICAgd2luZG93LmFkZEV2ZW50TGlzdGVuZXIoJ3Jlc2l6ZScsIHJlc2l6ZUhhbmRsZXIpO1xuICAgIHdpbmRvdy5hZGRFdmVudExpc3RlbmVyKCdvcmllbnRhdGlvbmNoYW5nZScsIG9yaWVudGF0aW9uQ2hhbmdlSGFuZGxlcik7XG4gIH0pO1xuICBvbignZGVzdHJveScsICgpID0+IHtcbiAgICByZW1vdmVPYnNlcnZlcigpO1xuICAgIHdpbmRvdy5yZW1vdmVFdmVudExpc3RlbmVyKCdyZXNpemUnLCByZXNpemVIYW5kbGVyKTtcbiAgICB3aW5kb3cucmVtb3ZlRXZlbnRMaXN0ZW5lcignb3JpZW50YXRpb25jaGFuZ2UnLCBvcmllbnRhdGlvbkNoYW5nZUhhbmRsZXIpO1xuICB9KTtcbn1cblxuZnVuY3Rpb24gT2JzZXJ2ZXIoX3JlZikge1xuICBsZXQge1xuICAgIHN3aXBlcixcbiAgICBleHRlbmRQYXJhbXMsXG4gICAgb24sXG4gICAgZW1pdFxuICB9ID0gX3JlZjtcbiAgY29uc3Qgb2JzZXJ2ZXJzID0gW107XG4gIGNvbnN0IHdpbmRvdyA9IGdldFdpbmRvdygpO1xuICBjb25zdCBhdHRhY2ggPSBmdW5jdGlvbiAodGFyZ2V0LCBvcHRpb25zKSB7XG4gICAgaWYgKG9wdGlvbnMgPT09IHZvaWQgMCkge1xuICAgICAgb3B0aW9ucyA9IHt9O1xuICAgIH1cbiAgICBjb25zdCBPYnNlcnZlckZ1bmMgPSB3aW5kb3cuTXV0YXRpb25PYnNlcnZlciB8fCB3aW5kb3cuV2Via2l0TXV0YXRpb25PYnNlcnZlcjtcbiAgICBjb25zdCBvYnNlcnZlciA9IG5ldyBPYnNlcnZlckZ1bmMobXV0YXRpb25zID0+IHtcbiAgICAgIC8vIFRoZSBvYnNlcnZlclVwZGF0ZSBldmVudCBzaG91bGQgb25seSBiZSB0cmlnZ2VyZWRcbiAgICAgIC8vIG9uY2UgZGVzcGl0ZSB0aGUgbnVtYmVyIG9mIG11dGF0aW9ucy4gIEFkZGl0aW9uYWxcbiAgICAgIC8vIHRyaWdnZXJzIGFyZSByZWR1bmRhbnQgYW5kIGFyZSB2ZXJ5IGNvc3RseVxuICAgICAgaWYgKHN3aXBlci5fX3ByZXZlbnRPYnNlcnZlcl9fKSByZXR1cm47XG4gICAgICBpZiAobXV0YXRpb25zLmxlbmd0aCA9PT0gMSkge1xuICAgICAgICBlbWl0KCdvYnNlcnZlclVwZGF0ZScsIG11dGF0aW9uc1swXSk7XG4gICAgICAgIHJldHVybjtcbiAgICAgIH1cbiAgICAgIGNvbnN0IG9ic2VydmVyVXBkYXRlID0gZnVuY3Rpb24gb2JzZXJ2ZXJVcGRhdGUoKSB7XG4gICAgICAgIGVtaXQoJ29ic2VydmVyVXBkYXRlJywgbXV0YXRpb25zWzBdKTtcbiAgICAgIH07XG4gICAgICBpZiAod2luZG93LnJlcXVlc3RBbmltYXRpb25GcmFtZSkge1xuICAgICAgICB3aW5kb3cucmVxdWVzdEFuaW1hdGlvbkZyYW1lKG9ic2VydmVyVXBkYXRlKTtcbiAgICAgIH0gZWxzZSB7XG4gICAgICAgIHdpbmRvdy5zZXRUaW1lb3V0KG9ic2VydmVyVXBkYXRlLCAwKTtcbiAgICAgIH1cbiAgICB9KTtcbiAgICBvYnNlcnZlci5vYnNlcnZlKHRhcmdldCwge1xuICAgICAgYXR0cmlidXRlczogdHlwZW9mIG9wdGlvbnMuYXR0cmlidXRlcyA9PT0gJ3VuZGVmaW5lZCcgPyB0cnVlIDogb3B0aW9ucy5hdHRyaWJ1dGVzLFxuICAgICAgY2hpbGRMaXN0OiBzd2lwZXIuaXNFbGVtZW50IHx8ICh0eXBlb2Ygb3B0aW9ucy5jaGlsZExpc3QgPT09ICd1bmRlZmluZWQnID8gdHJ1ZSA6IG9wdGlvbnMpLmNoaWxkTGlzdCxcbiAgICAgIGNoYXJhY3RlckRhdGE6IHR5cGVvZiBvcHRpb25zLmNoYXJhY3RlckRhdGEgPT09ICd1bmRlZmluZWQnID8gdHJ1ZSA6IG9wdGlvbnMuY2hhcmFjdGVyRGF0YVxuICAgIH0pO1xuICAgIG9ic2VydmVycy5wdXNoKG9ic2VydmVyKTtcbiAgfTtcbiAgY29uc3QgaW5pdCA9ICgpID0+IHtcbiAgICBpZiAoIXN3aXBlci5wYXJhbXMub2JzZXJ2ZXIpIHJldHVybjtcbiAgICBpZiAoc3dpcGVyLnBhcmFtcy5vYnNlcnZlUGFyZW50cykge1xuICAgICAgY29uc3QgY29udGFpbmVyUGFyZW50cyA9IGVsZW1lbnRQYXJlbnRzKHN3aXBlci5ob3N0RWwpO1xuICAgICAgZm9yIChsZXQgaSA9IDA7IGkgPCBjb250YWluZXJQYXJlbnRzLmxlbmd0aDsgaSArPSAxKSB7XG4gICAgICAgIGF0dGFjaChjb250YWluZXJQYXJlbnRzW2ldKTtcbiAgICAgIH1cbiAgICB9XG4gICAgLy8gT2JzZXJ2ZSBjb250YWluZXJcbiAgICBhdHRhY2goc3dpcGVyLmhvc3RFbCwge1xuICAgICAgY2hpbGRMaXN0OiBzd2lwZXIucGFyYW1zLm9ic2VydmVTbGlkZUNoaWxkcmVuXG4gICAgfSk7XG5cbiAgICAvLyBPYnNlcnZlIHdyYXBwZXJcbiAgICBhdHRhY2goc3dpcGVyLndyYXBwZXJFbCwge1xuICAgICAgYXR0cmlidXRlczogZmFsc2VcbiAgICB9KTtcbiAgfTtcbiAgY29uc3QgZGVzdHJveSA9ICgpID0+IHtcbiAgICBvYnNlcnZlcnMuZm9yRWFjaChvYnNlcnZlciA9PiB7XG4gICAgICBvYnNlcnZlci5kaXNjb25uZWN0KCk7XG4gICAgfSk7XG4gICAgb2JzZXJ2ZXJzLnNwbGljZSgwLCBvYnNlcnZlcnMubGVuZ3RoKTtcbiAgfTtcbiAgZXh0ZW5kUGFyYW1zKHtcbiAgICBvYnNlcnZlcjogZmFsc2UsXG4gICAgb2JzZXJ2ZVBhcmVudHM6IGZhbHNlLFxuICAgIG9ic2VydmVTbGlkZUNoaWxkcmVuOiBmYWxzZVxuICB9KTtcbiAgb24oJ2luaXQnLCBpbml0KTtcbiAgb24oJ2Rlc3Ryb3knLCBkZXN0cm95KTtcbn1cblxuLyogZXNsaW50LWRpc2FibGUgbm8tdW5kZXJzY29yZS1kYW5nbGUgKi9cblxudmFyIGV2ZW50c0VtaXR0ZXIgPSB7XG4gIG9uKGV2ZW50cywgaGFuZGxlciwgcHJpb3JpdHkpIHtcbiAgICBjb25zdCBzZWxmID0gdGhpcztcbiAgICBpZiAoIXNlbGYuZXZlbnRzTGlzdGVuZXJzIHx8IHNlbGYuZGVzdHJveWVkKSByZXR1cm4gc2VsZjtcbiAgICBpZiAodHlwZW9mIGhhbmRsZXIgIT09ICdmdW5jdGlvbicpIHJldHVybiBzZWxmO1xuICAgIGNvbnN0IG1ldGhvZCA9IHByaW9yaXR5ID8gJ3Vuc2hpZnQnIDogJ3B1c2gnO1xuICAgIGV2ZW50cy5zcGxpdCgnICcpLmZvckVhY2goZXZlbnQgPT4ge1xuICAgICAgaWYgKCFzZWxmLmV2ZW50c0xpc3RlbmVyc1tldmVudF0pIHNlbGYuZXZlbnRzTGlzdGVuZXJzW2V2ZW50XSA9IFtdO1xuICAgICAgc2VsZi5ldmVudHNMaXN0ZW5lcnNbZXZlbnRdW21ldGhvZF0oaGFuZGxlcik7XG4gICAgfSk7XG4gICAgcmV0dXJuIHNlbGY7XG4gIH0sXG4gIG9uY2UoZXZlbnRzLCBoYW5kbGVyLCBwcmlvcml0eSkge1xuICAgIGNvbnN0IHNlbGYgPSB0aGlzO1xuICAgIGlmICghc2VsZi5ldmVudHNMaXN0ZW5lcnMgfHwgc2VsZi5kZXN0cm95ZWQpIHJldHVybiBzZWxmO1xuICAgIGlmICh0eXBlb2YgaGFuZGxlciAhPT0gJ2Z1bmN0aW9uJykgcmV0dXJuIHNlbGY7XG4gICAgZnVuY3Rpb24gb25jZUhhbmRsZXIoKSB7XG4gICAgICBzZWxmLm9mZihldmVudHMsIG9uY2VIYW5kbGVyKTtcbiAgICAgIGlmIChvbmNlSGFuZGxlci5fX2VtaXR0ZXJQcm94eSkge1xuICAgICAgICBkZWxldGUgb25jZUhhbmRsZXIuX19lbWl0dGVyUHJveHk7XG4gICAgICB9XG4gICAgICBmb3IgKHZhciBfbGVuID0gYXJndW1lbnRzLmxlbmd0aCwgYXJncyA9IG5ldyBBcnJheShfbGVuKSwgX2tleSA9IDA7IF9rZXkgPCBfbGVuOyBfa2V5KyspIHtcbiAgICAgICAgYXJnc1tfa2V5XSA9IGFyZ3VtZW50c1tfa2V5XTtcbiAgICAgIH1cbiAgICAgIGhhbmRsZXIuYXBwbHkoc2VsZiwgYXJncyk7XG4gICAgfVxuICAgIG9uY2VIYW5kbGVyLl9fZW1pdHRlclByb3h5ID0gaGFuZGxlcjtcbiAgICByZXR1cm4gc2VsZi5vbihldmVudHMsIG9uY2VIYW5kbGVyLCBwcmlvcml0eSk7XG4gIH0sXG4gIG9uQW55KGhhbmRsZXIsIHByaW9yaXR5KSB7XG4gICAgY29uc3Qgc2VsZiA9IHRoaXM7XG4gICAgaWYgKCFzZWxmLmV2ZW50c0xpc3RlbmVycyB8fCBzZWxmLmRlc3Ryb3llZCkgcmV0dXJuIHNlbGY7XG4gICAgaWYgKHR5cGVvZiBoYW5kbGVyICE9PSAnZnVuY3Rpb24nKSByZXR1cm4gc2VsZjtcbiAgICBjb25zdCBtZXRob2QgPSBwcmlvcml0eSA/ICd1bnNoaWZ0JyA6ICdwdXNoJztcbiAgICBpZiAoc2VsZi5ldmVudHNBbnlMaXN0ZW5lcnMuaW5kZXhPZihoYW5kbGVyKSA8IDApIHtcbiAgICAgIHNlbGYuZXZlbnRzQW55TGlzdGVuZXJzW21ldGhvZF0oaGFuZGxlcik7XG4gICAgfVxuICAgIHJldHVybiBzZWxmO1xuICB9LFxuICBvZmZBbnkoaGFuZGxlcikge1xuICAgIGNvbnN0IHNlbGYgPSB0aGlzO1xuICAgIGlmICghc2VsZi5ldmVudHNMaXN0ZW5lcnMgfHwgc2VsZi5kZXN0cm95ZWQpIHJldHVybiBzZWxmO1xuICAgIGlmICghc2VsZi5ldmVudHNBbnlMaXN0ZW5lcnMpIHJldHVybiBzZWxmO1xuICAgIGNvbnN0IGluZGV4ID0gc2VsZi5ldmVudHNBbnlMaXN0ZW5lcnMuaW5kZXhPZihoYW5kbGVyKTtcbiAgICBpZiAoaW5kZXggPj0gMCkge1xuICAgICAgc2VsZi5ldmVudHNBbnlMaXN0ZW5lcnMuc3BsaWNlKGluZGV4LCAxKTtcbiAgICB9XG4gICAgcmV0dXJuIHNlbGY7XG4gIH0sXG4gIG9mZihldmVudHMsIGhhbmRsZXIpIHtcbiAgICBjb25zdCBzZWxmID0gdGhpcztcbiAgICBpZiAoIXNlbGYuZXZlbnRzTGlzdGVuZXJzIHx8IHNlbGYuZGVzdHJveWVkKSByZXR1cm4gc2VsZjtcbiAgICBpZiAoIXNlbGYuZXZlbnRzTGlzdGVuZXJzKSByZXR1cm4gc2VsZjtcbiAgICBldmVudHMuc3BsaXQoJyAnKS5mb3JFYWNoKGV2ZW50ID0+IHtcbiAgICAgIGlmICh0eXBlb2YgaGFuZGxlciA9PT0gJ3VuZGVmaW5lZCcpIHtcbiAgICAgICAgc2VsZi5ldmVudHNMaXN0ZW5lcnNbZXZlbnRdID0gW107XG4gICAgICB9IGVsc2UgaWYgKHNlbGYuZXZlbnRzTGlzdGVuZXJzW2V2ZW50XSkge1xuICAgICAgICBzZWxmLmV2ZW50c0xpc3RlbmVyc1tldmVudF0uZm9yRWFjaCgoZXZlbnRIYW5kbGVyLCBpbmRleCkgPT4ge1xuICAgICAgICAgIGlmIChldmVudEhhbmRsZXIgPT09IGhhbmRsZXIgfHwgZXZlbnRIYW5kbGVyLl9fZW1pdHRlclByb3h5ICYmIGV2ZW50SGFuZGxlci5fX2VtaXR0ZXJQcm94eSA9PT0gaGFuZGxlcikge1xuICAgICAgICAgICAgc2VsZi5ldmVudHNMaXN0ZW5lcnNbZXZlbnRdLnNwbGljZShpbmRleCwgMSk7XG4gICAgICAgICAgfVxuICAgICAgICB9KTtcbiAgICAgIH1cbiAgICB9KTtcbiAgICByZXR1cm4gc2VsZjtcbiAgfSxcbiAgZW1pdCgpIHtcbiAgICBjb25zdCBzZWxmID0gdGhpcztcbiAgICBpZiAoIXNlbGYuZXZlbnRzTGlzdGVuZXJzIHx8IHNlbGYuZGVzdHJveWVkKSByZXR1cm4gc2VsZjtcbiAgICBpZiAoIXNlbGYuZXZlbnRzTGlzdGVuZXJzKSByZXR1cm4gc2VsZjtcbiAgICBsZXQgZXZlbnRzO1xuICAgIGxldCBkYXRhO1xuICAgIGxldCBjb250ZXh0O1xuICAgIGZvciAodmFyIF9sZW4yID0gYXJndW1lbnRzLmxlbmd0aCwgYXJncyA9IG5ldyBBcnJheShfbGVuMiksIF9rZXkyID0gMDsgX2tleTIgPCBfbGVuMjsgX2tleTIrKykge1xuICAgICAgYXJnc1tfa2V5Ml0gPSBhcmd1bWVudHNbX2tleTJdO1xuICAgIH1cbiAgICBpZiAodHlwZW9mIGFyZ3NbMF0gPT09ICdzdHJpbmcnIHx8IEFycmF5LmlzQXJyYXkoYXJnc1swXSkpIHtcbiAgICAgIGV2ZW50cyA9IGFyZ3NbMF07XG4gICAgICBkYXRhID0gYXJncy5zbGljZSgxLCBhcmdzLmxlbmd0aCk7XG4gICAgICBjb250ZXh0ID0gc2VsZjtcbiAgICB9IGVsc2Uge1xuICAgICAgZXZlbnRzID0gYXJnc1swXS5ldmVudHM7XG4gICAgICBkYXRhID0gYXJnc1swXS5kYXRhO1xuICAgICAgY29udGV4dCA9IGFyZ3NbMF0uY29udGV4dCB8fCBzZWxmO1xuICAgIH1cbiAgICBkYXRhLnVuc2hpZnQoY29udGV4dCk7XG4gICAgY29uc3QgZXZlbnRzQXJyYXkgPSBBcnJheS5pc0FycmF5KGV2ZW50cykgPyBldmVudHMgOiBldmVudHMuc3BsaXQoJyAnKTtcbiAgICBldmVudHNBcnJheS5mb3JFYWNoKGV2ZW50ID0+IHtcbiAgICAgIGlmIChzZWxmLmV2ZW50c0FueUxpc3RlbmVycyAmJiBzZWxmLmV2ZW50c0FueUxpc3RlbmVycy5sZW5ndGgpIHtcbiAgICAgICAgc2VsZi5ldmVudHNBbnlMaXN0ZW5lcnMuZm9yRWFjaChldmVudEhhbmRsZXIgPT4ge1xuICAgICAgICAgIGV2ZW50SGFuZGxlci5hcHBseShjb250ZXh0LCBbZXZlbnQsIC4uLmRhdGFdKTtcbiAgICAgICAgfSk7XG4gICAgICB9XG4gICAgICBpZiAoc2VsZi5ldmVudHNMaXN0ZW5lcnMgJiYgc2VsZi5ldmVudHNMaXN0ZW5lcnNbZXZlbnRdKSB7XG4gICAgICAgIHNlbGYuZXZlbnRzTGlzdGVuZXJzW2V2ZW50XS5mb3JFYWNoKGV2ZW50SGFuZGxlciA9PiB7XG4gICAgICAgICAgZXZlbnRIYW5kbGVyLmFwcGx5KGNvbnRleHQsIGRhdGEpO1xuICAgICAgICB9KTtcbiAgICAgIH1cbiAgICB9KTtcbiAgICByZXR1cm4gc2VsZjtcbiAgfVxufTtcblxuZnVuY3Rpb24gdXBkYXRlU2l6ZSgpIHtcbiAgY29uc3Qgc3dpcGVyID0gdGhpcztcbiAgbGV0IHdpZHRoO1xuICBsZXQgaGVpZ2h0O1xuICBjb25zdCBlbCA9IHN3aXBlci5lbDtcbiAgaWYgKHR5cGVvZiBzd2lwZXIucGFyYW1zLndpZHRoICE9PSAndW5kZWZpbmVkJyAmJiBzd2lwZXIucGFyYW1zLndpZHRoICE9PSBudWxsKSB7XG4gICAgd2lkdGggPSBzd2lwZXIucGFyYW1zLndpZHRoO1xuICB9IGVsc2Uge1xuICAgIHdpZHRoID0gZWwuY2xpZW50V2lkdGg7XG4gIH1cbiAgaWYgKHR5cGVvZiBzd2lwZXIucGFyYW1zLmhlaWdodCAhPT0gJ3VuZGVmaW5lZCcgJiYgc3dpcGVyLnBhcmFtcy5oZWlnaHQgIT09IG51bGwpIHtcbiAgICBoZWlnaHQgPSBzd2lwZXIucGFyYW1zLmhlaWdodDtcbiAgfSBlbHNlIHtcbiAgICBoZWlnaHQgPSBlbC5jbGllbnRIZWlnaHQ7XG4gIH1cbiAgaWYgKHdpZHRoID09PSAwICYmIHN3aXBlci5pc0hvcml6b250YWwoKSB8fCBoZWlnaHQgPT09IDAgJiYgc3dpcGVyLmlzVmVydGljYWwoKSkge1xuICAgIHJldHVybjtcbiAgfVxuXG4gIC8vIFN1YnRyYWN0IHBhZGRpbmdzXG4gIHdpZHRoID0gd2lkdGggLSBwYXJzZUludChlbGVtZW50U3R5bGUoZWwsICdwYWRkaW5nLWxlZnQnKSB8fCAwLCAxMCkgLSBwYXJzZUludChlbGVtZW50U3R5bGUoZWwsICdwYWRkaW5nLXJpZ2h0JykgfHwgMCwgMTApO1xuICBoZWlnaHQgPSBoZWlnaHQgLSBwYXJzZUludChlbGVtZW50U3R5bGUoZWwsICdwYWRkaW5nLXRvcCcpIHx8IDAsIDEwKSAtIHBhcnNlSW50KGVsZW1lbnRTdHlsZShlbCwgJ3BhZGRpbmctYm90dG9tJykgfHwgMCwgMTApO1xuICBpZiAoTnVtYmVyLmlzTmFOKHdpZHRoKSkgd2lkdGggPSAwO1xuICBpZiAoTnVtYmVyLmlzTmFOKGhlaWdodCkpIGhlaWdodCA9IDA7XG4gIE9iamVjdC5hc3NpZ24oc3dpcGVyLCB7XG4gICAgd2lkdGgsXG4gICAgaGVpZ2h0LFxuICAgIHNpemU6IHN3aXBlci5pc0hvcml6b250YWwoKSA/IHdpZHRoIDogaGVpZ2h0XG4gIH0pO1xufVxuXG5mdW5jdGlvbiB1cGRhdGVTbGlkZXMoKSB7XG4gIGNvbnN0IHN3aXBlciA9IHRoaXM7XG4gIGZ1bmN0aW9uIGdldERpcmVjdGlvblByb3BlcnR5VmFsdWUobm9kZSwgbGFiZWwpIHtcbiAgICByZXR1cm4gcGFyc2VGbG9hdChub2RlLmdldFByb3BlcnR5VmFsdWUoc3dpcGVyLmdldERpcmVjdGlvbkxhYmVsKGxhYmVsKSkgfHwgMCk7XG4gIH1cbiAgY29uc3QgcGFyYW1zID0gc3dpcGVyLnBhcmFtcztcbiAgY29uc3Qge1xuICAgIHdyYXBwZXJFbCxcbiAgICBzbGlkZXNFbCxcbiAgICBzaXplOiBzd2lwZXJTaXplLFxuICAgIHJ0bFRyYW5zbGF0ZTogcnRsLFxuICAgIHdyb25nUlRMXG4gIH0gPSBzd2lwZXI7XG4gIGNvbnN0IGlzVmlydHVhbCA9IHN3aXBlci52aXJ0dWFsICYmIHBhcmFtcy52aXJ0dWFsLmVuYWJsZWQ7XG4gIGNvbnN0IHByZXZpb3VzU2xpZGVzTGVuZ3RoID0gaXNWaXJ0dWFsID8gc3dpcGVyLnZpcnR1YWwuc2xpZGVzLmxlbmd0aCA6IHN3aXBlci5zbGlkZXMubGVuZ3RoO1xuICBjb25zdCBzbGlkZXMgPSBlbGVtZW50Q2hpbGRyZW4oc2xpZGVzRWwsIGAuJHtzd2lwZXIucGFyYW1zLnNsaWRlQ2xhc3N9LCBzd2lwZXItc2xpZGVgKTtcbiAgY29uc3Qgc2xpZGVzTGVuZ3RoID0gaXNWaXJ0dWFsID8gc3dpcGVyLnZpcnR1YWwuc2xpZGVzLmxlbmd0aCA6IHNsaWRlcy5sZW5ndGg7XG4gIGxldCBzbmFwR3JpZCA9IFtdO1xuICBjb25zdCBzbGlkZXNHcmlkID0gW107XG4gIGNvbnN0IHNsaWRlc1NpemVzR3JpZCA9IFtdO1xuICBsZXQgb2Zmc2V0QmVmb3JlID0gcGFyYW1zLnNsaWRlc09mZnNldEJlZm9yZTtcbiAgaWYgKHR5cGVvZiBvZmZzZXRCZWZvcmUgPT09ICdmdW5jdGlvbicpIHtcbiAgICBvZmZzZXRCZWZvcmUgPSBwYXJhbXMuc2xpZGVzT2Zmc2V0QmVmb3JlLmNhbGwoc3dpcGVyKTtcbiAgfVxuICBsZXQgb2Zmc2V0QWZ0ZXIgPSBwYXJhbXMuc2xpZGVzT2Zmc2V0QWZ0ZXI7XG4gIGlmICh0eXBlb2Ygb2Zmc2V0QWZ0ZXIgPT09ICdmdW5jdGlvbicpIHtcbiAgICBvZmZzZXRBZnRlciA9IHBhcmFtcy5zbGlkZXNPZmZzZXRBZnRlci5jYWxsKHN3aXBlcik7XG4gIH1cbiAgY29uc3QgcHJldmlvdXNTbmFwR3JpZExlbmd0aCA9IHN3aXBlci5zbmFwR3JpZC5sZW5ndGg7XG4gIGNvbnN0IHByZXZpb3VzU2xpZGVzR3JpZExlbmd0aCA9IHN3aXBlci5zbGlkZXNHcmlkLmxlbmd0aDtcbiAgbGV0IHNwYWNlQmV0d2VlbiA9IHBhcmFtcy5zcGFjZUJldHdlZW47XG4gIGxldCBzbGlkZVBvc2l0aW9uID0gLW9mZnNldEJlZm9yZTtcbiAgbGV0IHByZXZTbGlkZVNpemUgPSAwO1xuICBsZXQgaW5kZXggPSAwO1xuICBpZiAodHlwZW9mIHN3aXBlclNpemUgPT09ICd1bmRlZmluZWQnKSB7XG4gICAgcmV0dXJuO1xuICB9XG4gIGlmICh0eXBlb2Ygc3BhY2VCZXR3ZWVuID09PSAnc3RyaW5nJyAmJiBzcGFjZUJldHdlZW4uaW5kZXhPZignJScpID49IDApIHtcbiAgICBzcGFjZUJldHdlZW4gPSBwYXJzZUZsb2F0KHNwYWNlQmV0d2Vlbi5yZXBsYWNlKCclJywgJycpKSAvIDEwMCAqIHN3aXBlclNpemU7XG4gIH0gZWxzZSBpZiAodHlwZW9mIHNwYWNlQmV0d2VlbiA9PT0gJ3N0cmluZycpIHtcbiAgICBzcGFjZUJldHdlZW4gPSBwYXJzZUZsb2F0KHNwYWNlQmV0d2Vlbik7XG4gIH1cbiAgc3dpcGVyLnZpcnR1YWxTaXplID0gLXNwYWNlQmV0d2VlbjtcblxuICAvLyByZXNldCBtYXJnaW5zXG4gIHNsaWRlcy5mb3JFYWNoKHNsaWRlRWwgPT4ge1xuICAgIGlmIChydGwpIHtcbiAgICAgIHNsaWRlRWwuc3R5bGUubWFyZ2luTGVmdCA9ICcnO1xuICAgIH0gZWxzZSB7XG4gICAgICBzbGlkZUVsLnN0eWxlLm1hcmdpblJpZ2h0ID0gJyc7XG4gICAgfVxuICAgIHNsaWRlRWwuc3R5bGUubWFyZ2luQm90dG9tID0gJyc7XG4gICAgc2xpZGVFbC5zdHlsZS5tYXJnaW5Ub3AgPSAnJztcbiAgfSk7XG5cbiAgLy8gcmVzZXQgY3NzTW9kZSBvZmZzZXRzXG4gIGlmIChwYXJhbXMuY2VudGVyZWRTbGlkZXMgJiYgcGFyYW1zLmNzc01vZGUpIHtcbiAgICBzZXRDU1NQcm9wZXJ0eSh3cmFwcGVyRWwsICctLXN3aXBlci1jZW50ZXJlZC1vZmZzZXQtYmVmb3JlJywgJycpO1xuICAgIHNldENTU1Byb3BlcnR5KHdyYXBwZXJFbCwgJy0tc3dpcGVyLWNlbnRlcmVkLW9mZnNldC1hZnRlcicsICcnKTtcbiAgfVxuICBjb25zdCBncmlkRW5hYmxlZCA9IHBhcmFtcy5ncmlkICYmIHBhcmFtcy5ncmlkLnJvd3MgPiAxICYmIHN3aXBlci5ncmlkO1xuICBpZiAoZ3JpZEVuYWJsZWQpIHtcbiAgICBzd2lwZXIuZ3JpZC5pbml0U2xpZGVzKHNsaWRlcyk7XG4gIH0gZWxzZSBpZiAoc3dpcGVyLmdyaWQpIHtcbiAgICBzd2lwZXIuZ3JpZC51bnNldFNsaWRlcygpO1xuICB9XG5cbiAgLy8gQ2FsYyBzbGlkZXNcbiAgbGV0IHNsaWRlU2l6ZTtcbiAgY29uc3Qgc2hvdWxkUmVzZXRTbGlkZVNpemUgPSBwYXJhbXMuc2xpZGVzUGVyVmlldyA9PT0gJ2F1dG8nICYmIHBhcmFtcy5icmVha3BvaW50cyAmJiBPYmplY3Qua2V5cyhwYXJhbXMuYnJlYWtwb2ludHMpLmZpbHRlcihrZXkgPT4ge1xuICAgIHJldHVybiB0eXBlb2YgcGFyYW1zLmJyZWFrcG9pbnRzW2tleV0uc2xpZGVzUGVyVmlldyAhPT0gJ3VuZGVmaW5lZCc7XG4gIH0pLmxlbmd0aCA+IDA7XG4gIGZvciAobGV0IGkgPSAwOyBpIDwgc2xpZGVzTGVuZ3RoOyBpICs9IDEpIHtcbiAgICBzbGlkZVNpemUgPSAwO1xuICAgIGxldCBzbGlkZTtcbiAgICBpZiAoc2xpZGVzW2ldKSBzbGlkZSA9IHNsaWRlc1tpXTtcbiAgICBpZiAoZ3JpZEVuYWJsZWQpIHtcbiAgICAgIHN3aXBlci5ncmlkLnVwZGF0ZVNsaWRlKGksIHNsaWRlLCBzbGlkZXMpO1xuICAgIH1cbiAgICBpZiAoc2xpZGVzW2ldICYmIGVsZW1lbnRTdHlsZShzbGlkZSwgJ2Rpc3BsYXknKSA9PT0gJ25vbmUnKSBjb250aW51ZTsgLy8gZXNsaW50LWRpc2FibGUtbGluZVxuXG4gICAgaWYgKHBhcmFtcy5zbGlkZXNQZXJWaWV3ID09PSAnYXV0bycpIHtcbiAgICAgIGlmIChzaG91bGRSZXNldFNsaWRlU2l6ZSkge1xuICAgICAgICBzbGlkZXNbaV0uc3R5bGVbc3dpcGVyLmdldERpcmVjdGlvbkxhYmVsKCd3aWR0aCcpXSA9IGBgO1xuICAgICAgfVxuICAgICAgY29uc3Qgc2xpZGVTdHlsZXMgPSBnZXRDb21wdXRlZFN0eWxlKHNsaWRlKTtcbiAgICAgIGNvbnN0IGN1cnJlbnRUcmFuc2Zvcm0gPSBzbGlkZS5zdHlsZS50cmFuc2Zvcm07XG4gICAgICBjb25zdCBjdXJyZW50V2ViS2l0VHJhbnNmb3JtID0gc2xpZGUuc3R5bGUud2Via2l0VHJhbnNmb3JtO1xuICAgICAgaWYgKGN1cnJlbnRUcmFuc2Zvcm0pIHtcbiAgICAgICAgc2xpZGUuc3R5bGUudHJhbnNmb3JtID0gJ25vbmUnO1xuICAgICAgfVxuICAgICAgaWYgKGN1cnJlbnRXZWJLaXRUcmFuc2Zvcm0pIHtcbiAgICAgICAgc2xpZGUuc3R5bGUud2Via2l0VHJhbnNmb3JtID0gJ25vbmUnO1xuICAgICAgfVxuICAgICAgaWYgKHBhcmFtcy5yb3VuZExlbmd0aHMpIHtcbiAgICAgICAgc2xpZGVTaXplID0gc3dpcGVyLmlzSG9yaXpvbnRhbCgpID8gZWxlbWVudE91dGVyU2l6ZShzbGlkZSwgJ3dpZHRoJywgdHJ1ZSkgOiBlbGVtZW50T3V0ZXJTaXplKHNsaWRlLCAnaGVpZ2h0JywgdHJ1ZSk7XG4gICAgICB9IGVsc2Uge1xuICAgICAgICAvLyBlc2xpbnQtZGlzYWJsZS1uZXh0LWxpbmVcbiAgICAgICAgY29uc3Qgd2lkdGggPSBnZXREaXJlY3Rpb25Qcm9wZXJ0eVZhbHVlKHNsaWRlU3R5bGVzLCAnd2lkdGgnKTtcbiAgICAgICAgY29uc3QgcGFkZGluZ0xlZnQgPSBnZXREaXJlY3Rpb25Qcm9wZXJ0eVZhbHVlKHNsaWRlU3R5bGVzLCAncGFkZGluZy1sZWZ0Jyk7XG4gICAgICAgIGNvbnN0IHBhZGRpbmdSaWdodCA9IGdldERpcmVjdGlvblByb3BlcnR5VmFsdWUoc2xpZGVTdHlsZXMsICdwYWRkaW5nLXJpZ2h0Jyk7XG4gICAgICAgIGNvbnN0IG1hcmdpbkxlZnQgPSBnZXREaXJlY3Rpb25Qcm9wZXJ0eVZhbHVlKHNsaWRlU3R5bGVzLCAnbWFyZ2luLWxlZnQnKTtcbiAgICAgICAgY29uc3QgbWFyZ2luUmlnaHQgPSBnZXREaXJlY3Rpb25Qcm9wZXJ0eVZhbHVlKHNsaWRlU3R5bGVzLCAnbWFyZ2luLXJpZ2h0Jyk7XG4gICAgICAgIGNvbnN0IGJveFNpemluZyA9IHNsaWRlU3R5bGVzLmdldFByb3BlcnR5VmFsdWUoJ2JveC1zaXppbmcnKTtcbiAgICAgICAgaWYgKGJveFNpemluZyAmJiBib3hTaXppbmcgPT09ICdib3JkZXItYm94Jykge1xuICAgICAgICAgIHNsaWRlU2l6ZSA9IHdpZHRoICsgbWFyZ2luTGVmdCArIG1hcmdpblJpZ2h0O1xuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgIGNvbnN0IHtcbiAgICAgICAgICAgIGNsaWVudFdpZHRoLFxuICAgICAgICAgICAgb2Zmc2V0V2lkdGhcbiAgICAgICAgICB9ID0gc2xpZGU7XG4gICAgICAgICAgc2xpZGVTaXplID0gd2lkdGggKyBwYWRkaW5nTGVmdCArIHBhZGRpbmdSaWdodCArIG1hcmdpbkxlZnQgKyBtYXJnaW5SaWdodCArIChvZmZzZXRXaWR0aCAtIGNsaWVudFdpZHRoKTtcbiAgICAgICAgfVxuICAgICAgfVxuICAgICAgaWYgKGN1cnJlbnRUcmFuc2Zvcm0pIHtcbiAgICAgICAgc2xpZGUuc3R5bGUudHJhbnNmb3JtID0gY3VycmVudFRyYW5zZm9ybTtcbiAgICAgIH1cbiAgICAgIGlmIChjdXJyZW50V2ViS2l0VHJhbnNmb3JtKSB7XG4gICAgICAgIHNsaWRlLnN0eWxlLndlYmtpdFRyYW5zZm9ybSA9IGN1cnJlbnRXZWJLaXRUcmFuc2Zvcm07XG4gICAgICB9XG4gICAgICBpZiAocGFyYW1zLnJvdW5kTGVuZ3Rocykgc2xpZGVTaXplID0gTWF0aC5mbG9vcihzbGlkZVNpemUpO1xuICAgIH0gZWxzZSB7XG4gICAgICBzbGlkZVNpemUgPSAoc3dpcGVyU2l6ZSAtIChwYXJhbXMuc2xpZGVzUGVyVmlldyAtIDEpICogc3BhY2VCZXR3ZWVuKSAvIHBhcmFtcy5zbGlkZXNQZXJWaWV3O1xuICAgICAgaWYgKHBhcmFtcy5yb3VuZExlbmd0aHMpIHNsaWRlU2l6ZSA9IE1hdGguZmxvb3Ioc2xpZGVTaXplKTtcbiAgICAgIGlmIChzbGlkZXNbaV0pIHtcbiAgICAgICAgc2xpZGVzW2ldLnN0eWxlW3N3aXBlci5nZXREaXJlY3Rpb25MYWJlbCgnd2lkdGgnKV0gPSBgJHtzbGlkZVNpemV9cHhgO1xuICAgICAgfVxuICAgIH1cbiAgICBpZiAoc2xpZGVzW2ldKSB7XG4gICAgICBzbGlkZXNbaV0uc3dpcGVyU2xpZGVTaXplID0gc2xpZGVTaXplO1xuICAgIH1cbiAgICBzbGlkZXNTaXplc0dyaWQucHVzaChzbGlkZVNpemUpO1xuICAgIGlmIChwYXJhbXMuY2VudGVyZWRTbGlkZXMpIHtcbiAgICAgIHNsaWRlUG9zaXRpb24gPSBzbGlkZVBvc2l0aW9uICsgc2xpZGVTaXplIC8gMiArIHByZXZTbGlkZVNpemUgLyAyICsgc3BhY2VCZXR3ZWVuO1xuICAgICAgaWYgKHByZXZTbGlkZVNpemUgPT09IDAgJiYgaSAhPT0gMCkgc2xpZGVQb3NpdGlvbiA9IHNsaWRlUG9zaXRpb24gLSBzd2lwZXJTaXplIC8gMiAtIHNwYWNlQmV0d2VlbjtcbiAgICAgIGlmIChpID09PSAwKSBzbGlkZVBvc2l0aW9uID0gc2xpZGVQb3NpdGlvbiAtIHN3aXBlclNpemUgLyAyIC0gc3BhY2VCZXR3ZWVuO1xuICAgICAgaWYgKE1hdGguYWJzKHNsaWRlUG9zaXRpb24pIDwgMSAvIDEwMDApIHNsaWRlUG9zaXRpb24gPSAwO1xuICAgICAgaWYgKHBhcmFtcy5yb3VuZExlbmd0aHMpIHNsaWRlUG9zaXRpb24gPSBNYXRoLmZsb29yKHNsaWRlUG9zaXRpb24pO1xuICAgICAgaWYgKGluZGV4ICUgcGFyYW1zLnNsaWRlc1Blckdyb3VwID09PSAwKSBzbmFwR3JpZC5wdXNoKHNsaWRlUG9zaXRpb24pO1xuICAgICAgc2xpZGVzR3JpZC5wdXNoKHNsaWRlUG9zaXRpb24pO1xuICAgIH0gZWxzZSB7XG4gICAgICBpZiAocGFyYW1zLnJvdW5kTGVuZ3Rocykgc2xpZGVQb3NpdGlvbiA9IE1hdGguZmxvb3Ioc2xpZGVQb3NpdGlvbik7XG4gICAgICBpZiAoKGluZGV4IC0gTWF0aC5taW4oc3dpcGVyLnBhcmFtcy5zbGlkZXNQZXJHcm91cFNraXAsIGluZGV4KSkgJSBzd2lwZXIucGFyYW1zLnNsaWRlc1Blckdyb3VwID09PSAwKSBzbmFwR3JpZC5wdXNoKHNsaWRlUG9zaXRpb24pO1xuICAgICAgc2xpZGVzR3JpZC5wdXNoKHNsaWRlUG9zaXRpb24pO1xuICAgICAgc2xpZGVQb3NpdGlvbiA9IHNsaWRlUG9zaXRpb24gKyBzbGlkZVNpemUgKyBzcGFjZUJldHdlZW47XG4gICAgfVxuICAgIHN3aXBlci52aXJ0dWFsU2l6ZSArPSBzbGlkZVNpemUgKyBzcGFjZUJldHdlZW47XG4gICAgcHJldlNsaWRlU2l6ZSA9IHNsaWRlU2l6ZTtcbiAgICBpbmRleCArPSAxO1xuICB9XG4gIHN3aXBlci52aXJ0dWFsU2l6ZSA9IE1hdGgubWF4KHN3aXBlci52aXJ0dWFsU2l6ZSwgc3dpcGVyU2l6ZSkgKyBvZmZzZXRBZnRlcjtcbiAgaWYgKHJ0bCAmJiB3cm9uZ1JUTCAmJiAocGFyYW1zLmVmZmVjdCA9PT0gJ3NsaWRlJyB8fCBwYXJhbXMuZWZmZWN0ID09PSAnY292ZXJmbG93JykpIHtcbiAgICB3cmFwcGVyRWwuc3R5bGUud2lkdGggPSBgJHtzd2lwZXIudmlydHVhbFNpemUgKyBzcGFjZUJldHdlZW59cHhgO1xuICB9XG4gIGlmIChwYXJhbXMuc2V0V3JhcHBlclNpemUpIHtcbiAgICB3cmFwcGVyRWwuc3R5bGVbc3dpcGVyLmdldERpcmVjdGlvbkxhYmVsKCd3aWR0aCcpXSA9IGAke3N3aXBlci52aXJ0dWFsU2l6ZSArIHNwYWNlQmV0d2Vlbn1weGA7XG4gIH1cbiAgaWYgKGdyaWRFbmFibGVkKSB7XG4gICAgc3dpcGVyLmdyaWQudXBkYXRlV3JhcHBlclNpemUoc2xpZGVTaXplLCBzbmFwR3JpZCk7XG4gIH1cblxuICAvLyBSZW1vdmUgbGFzdCBncmlkIGVsZW1lbnRzIGRlcGVuZGluZyBvbiB3aWR0aFxuICBpZiAoIXBhcmFtcy5jZW50ZXJlZFNsaWRlcykge1xuICAgIGNvbnN0IG5ld1NsaWRlc0dyaWQgPSBbXTtcbiAgICBmb3IgKGxldCBpID0gMDsgaSA8IHNuYXBHcmlkLmxlbmd0aDsgaSArPSAxKSB7XG4gICAgICBsZXQgc2xpZGVzR3JpZEl0ZW0gPSBzbmFwR3JpZFtpXTtcbiAgICAgIGlmIChwYXJhbXMucm91bmRMZW5ndGhzKSBzbGlkZXNHcmlkSXRlbSA9IE1hdGguZmxvb3Ioc2xpZGVzR3JpZEl0ZW0pO1xuICAgICAgaWYgKHNuYXBHcmlkW2ldIDw9IHN3aXBlci52aXJ0dWFsU2l6ZSAtIHN3aXBlclNpemUpIHtcbiAgICAgICAgbmV3U2xpZGVzR3JpZC5wdXNoKHNsaWRlc0dyaWRJdGVtKTtcbiAgICAgIH1cbiAgICB9XG4gICAgc25hcEdyaWQgPSBuZXdTbGlkZXNHcmlkO1xuICAgIGlmIChNYXRoLmZsb29yKHN3aXBlci52aXJ0dWFsU2l6ZSAtIHN3aXBlclNpemUpIC0gTWF0aC5mbG9vcihzbmFwR3JpZFtzbmFwR3JpZC5sZW5ndGggLSAxXSkgPiAxKSB7XG4gICAgICBzbmFwR3JpZC5wdXNoKHN3aXBlci52aXJ0dWFsU2l6ZSAtIHN3aXBlclNpemUpO1xuICAgIH1cbiAgfVxuICBpZiAoaXNWaXJ0dWFsICYmIHBhcmFtcy5sb29wKSB7XG4gICAgY29uc3Qgc2l6ZSA9IHNsaWRlc1NpemVzR3JpZFswXSArIHNwYWNlQmV0d2VlbjtcbiAgICBpZiAocGFyYW1zLnNsaWRlc1Blckdyb3VwID4gMSkge1xuICAgICAgY29uc3QgZ3JvdXBzID0gTWF0aC5jZWlsKChzd2lwZXIudmlydHVhbC5zbGlkZXNCZWZvcmUgKyBzd2lwZXIudmlydHVhbC5zbGlkZXNBZnRlcikgLyBwYXJhbXMuc2xpZGVzUGVyR3JvdXApO1xuICAgICAgY29uc3QgZ3JvdXBTaXplID0gc2l6ZSAqIHBhcmFtcy5zbGlkZXNQZXJHcm91cDtcbiAgICAgIGZvciAobGV0IGkgPSAwOyBpIDwgZ3JvdXBzOyBpICs9IDEpIHtcbiAgICAgICAgc25hcEdyaWQucHVzaChzbmFwR3JpZFtzbmFwR3JpZC5sZW5ndGggLSAxXSArIGdyb3VwU2l6ZSk7XG4gICAgICB9XG4gICAgfVxuICAgIGZvciAobGV0IGkgPSAwOyBpIDwgc3dpcGVyLnZpcnR1YWwuc2xpZGVzQmVmb3JlICsgc3dpcGVyLnZpcnR1YWwuc2xpZGVzQWZ0ZXI7IGkgKz0gMSkge1xuICAgICAgaWYgKHBhcmFtcy5zbGlkZXNQZXJHcm91cCA9PT0gMSkge1xuICAgICAgICBzbmFwR3JpZC5wdXNoKHNuYXBHcmlkW3NuYXBHcmlkLmxlbmd0aCAtIDFdICsgc2l6ZSk7XG4gICAgICB9XG4gICAgICBzbGlkZXNHcmlkLnB1c2goc2xpZGVzR3JpZFtzbGlkZXNHcmlkLmxlbmd0aCAtIDFdICsgc2l6ZSk7XG4gICAgICBzd2lwZXIudmlydHVhbFNpemUgKz0gc2l6ZTtcbiAgICB9XG4gIH1cbiAgaWYgKHNuYXBHcmlkLmxlbmd0aCA9PT0gMCkgc25hcEdyaWQgPSBbMF07XG4gIGlmIChzcGFjZUJldHdlZW4gIT09IDApIHtcbiAgICBjb25zdCBrZXkgPSBzd2lwZXIuaXNIb3Jpem9udGFsKCkgJiYgcnRsID8gJ21hcmdpbkxlZnQnIDogc3dpcGVyLmdldERpcmVjdGlvbkxhYmVsKCdtYXJnaW5SaWdodCcpO1xuICAgIHNsaWRlcy5maWx0ZXIoKF8sIHNsaWRlSW5kZXgpID0+IHtcbiAgICAgIGlmICghcGFyYW1zLmNzc01vZGUgfHwgcGFyYW1zLmxvb3ApIHJldHVybiB0cnVlO1xuICAgICAgaWYgKHNsaWRlSW5kZXggPT09IHNsaWRlcy5sZW5ndGggLSAxKSB7XG4gICAgICAgIHJldHVybiBmYWxzZTtcbiAgICAgIH1cbiAgICAgIHJldHVybiB0cnVlO1xuICAgIH0pLmZvckVhY2goc2xpZGVFbCA9PiB7XG4gICAgICBzbGlkZUVsLnN0eWxlW2tleV0gPSBgJHtzcGFjZUJldHdlZW59cHhgO1xuICAgIH0pO1xuICB9XG4gIGlmIChwYXJhbXMuY2VudGVyZWRTbGlkZXMgJiYgcGFyYW1zLmNlbnRlcmVkU2xpZGVzQm91bmRzKSB7XG4gICAgbGV0IGFsbFNsaWRlc1NpemUgPSAwO1xuICAgIHNsaWRlc1NpemVzR3JpZC5mb3JFYWNoKHNsaWRlU2l6ZVZhbHVlID0+IHtcbiAgICAgIGFsbFNsaWRlc1NpemUgKz0gc2xpZGVTaXplVmFsdWUgKyAoc3BhY2VCZXR3ZWVuIHx8IDApO1xuICAgIH0pO1xuICAgIGFsbFNsaWRlc1NpemUgLT0gc3BhY2VCZXR3ZWVuO1xuICAgIGNvbnN0IG1heFNuYXAgPSBhbGxTbGlkZXNTaXplID4gc3dpcGVyU2l6ZSA/IGFsbFNsaWRlc1NpemUgLSBzd2lwZXJTaXplIDogMDtcbiAgICBzbmFwR3JpZCA9IHNuYXBHcmlkLm1hcChzbmFwID0+IHtcbiAgICAgIGlmIChzbmFwIDw9IDApIHJldHVybiAtb2Zmc2V0QmVmb3JlO1xuICAgICAgaWYgKHNuYXAgPiBtYXhTbmFwKSByZXR1cm4gbWF4U25hcCArIG9mZnNldEFmdGVyO1xuICAgICAgcmV0dXJuIHNuYXA7XG4gICAgfSk7XG4gIH1cbiAgaWYgKHBhcmFtcy5jZW50ZXJJbnN1ZmZpY2llbnRTbGlkZXMpIHtcbiAgICBsZXQgYWxsU2xpZGVzU2l6ZSA9IDA7XG4gICAgc2xpZGVzU2l6ZXNHcmlkLmZvckVhY2goc2xpZGVTaXplVmFsdWUgPT4ge1xuICAgICAgYWxsU2xpZGVzU2l6ZSArPSBzbGlkZVNpemVWYWx1ZSArIChzcGFjZUJldHdlZW4gfHwgMCk7XG4gICAgfSk7XG4gICAgYWxsU2xpZGVzU2l6ZSAtPSBzcGFjZUJldHdlZW47XG4gICAgY29uc3Qgb2Zmc2V0U2l6ZSA9IChwYXJhbXMuc2xpZGVzT2Zmc2V0QmVmb3JlIHx8IDApICsgKHBhcmFtcy5zbGlkZXNPZmZzZXRBZnRlciB8fCAwKTtcbiAgICBpZiAoYWxsU2xpZGVzU2l6ZSArIG9mZnNldFNpemUgPCBzd2lwZXJTaXplKSB7XG4gICAgICBjb25zdCBhbGxTbGlkZXNPZmZzZXQgPSAoc3dpcGVyU2l6ZSAtIGFsbFNsaWRlc1NpemUgLSBvZmZzZXRTaXplKSAvIDI7XG4gICAgICBzbmFwR3JpZC5mb3JFYWNoKChzbmFwLCBzbmFwSW5kZXgpID0+IHtcbiAgICAgICAgc25hcEdyaWRbc25hcEluZGV4XSA9IHNuYXAgLSBhbGxTbGlkZXNPZmZzZXQ7XG4gICAgICB9KTtcbiAgICAgIHNsaWRlc0dyaWQuZm9yRWFjaCgoc25hcCwgc25hcEluZGV4KSA9PiB7XG4gICAgICAgIHNsaWRlc0dyaWRbc25hcEluZGV4XSA9IHNuYXAgKyBhbGxTbGlkZXNPZmZzZXQ7XG4gICAgICB9KTtcbiAgICB9XG4gIH1cbiAgT2JqZWN0LmFzc2lnbihzd2lwZXIsIHtcbiAgICBzbGlkZXMsXG4gICAgc25hcEdyaWQsXG4gICAgc2xpZGVzR3JpZCxcbiAgICBzbGlkZXNTaXplc0dyaWRcbiAgfSk7XG4gIGlmIChwYXJhbXMuY2VudGVyZWRTbGlkZXMgJiYgcGFyYW1zLmNzc01vZGUgJiYgIXBhcmFtcy5jZW50ZXJlZFNsaWRlc0JvdW5kcykge1xuICAgIHNldENTU1Byb3BlcnR5KHdyYXBwZXJFbCwgJy0tc3dpcGVyLWNlbnRlcmVkLW9mZnNldC1iZWZvcmUnLCBgJHstc25hcEdyaWRbMF19cHhgKTtcbiAgICBzZXRDU1NQcm9wZXJ0eSh3cmFwcGVyRWwsICctLXN3aXBlci1jZW50ZXJlZC1vZmZzZXQtYWZ0ZXInLCBgJHtzd2lwZXIuc2l6ZSAvIDIgLSBzbGlkZXNTaXplc0dyaWRbc2xpZGVzU2l6ZXNHcmlkLmxlbmd0aCAtIDFdIC8gMn1weGApO1xuICAgIGNvbnN0IGFkZFRvU25hcEdyaWQgPSAtc3dpcGVyLnNuYXBHcmlkWzBdO1xuICAgIGNvbnN0IGFkZFRvU2xpZGVzR3JpZCA9IC1zd2lwZXIuc2xpZGVzR3JpZFswXTtcbiAgICBzd2lwZXIuc25hcEdyaWQgPSBzd2lwZXIuc25hcEdyaWQubWFwKHYgPT4gdiArIGFkZFRvU25hcEdyaWQpO1xuICAgIHN3aXBlci5zbGlkZXNHcmlkID0gc3dpcGVyLnNsaWRlc0dyaWQubWFwKHYgPT4gdiArIGFkZFRvU2xpZGVzR3JpZCk7XG4gIH1cbiAgaWYgKHNsaWRlc0xlbmd0aCAhPT0gcHJldmlvdXNTbGlkZXNMZW5ndGgpIHtcbiAgICBzd2lwZXIuZW1pdCgnc2xpZGVzTGVuZ3RoQ2hhbmdlJyk7XG4gIH1cbiAgaWYgKHNuYXBHcmlkLmxlbmd0aCAhPT0gcHJldmlvdXNTbmFwR3JpZExlbmd0aCkge1xuICAgIGlmIChzd2lwZXIucGFyYW1zLndhdGNoT3ZlcmZsb3cpIHN3aXBlci5jaGVja092ZXJmbG93KCk7XG4gICAgc3dpcGVyLmVtaXQoJ3NuYXBHcmlkTGVuZ3RoQ2hhbmdlJyk7XG4gIH1cbiAgaWYgKHNsaWRlc0dyaWQubGVuZ3RoICE9PSBwcmV2aW91c1NsaWRlc0dyaWRMZW5ndGgpIHtcbiAgICBzd2lwZXIuZW1pdCgnc2xpZGVzR3JpZExlbmd0aENoYW5nZScpO1xuICB9XG4gIGlmIChwYXJhbXMud2F0Y2hTbGlkZXNQcm9ncmVzcykge1xuICAgIHN3aXBlci51cGRhdGVTbGlkZXNPZmZzZXQoKTtcbiAgfVxuICBzd2lwZXIuZW1pdCgnc2xpZGVzVXBkYXRlZCcpO1xuICBpZiAoIWlzVmlydHVhbCAmJiAhcGFyYW1zLmNzc01vZGUgJiYgKHBhcmFtcy5lZmZlY3QgPT09ICdzbGlkZScgfHwgcGFyYW1zLmVmZmVjdCA9PT0gJ2ZhZGUnKSkge1xuICAgIGNvbnN0IGJhY2tGYWNlSGlkZGVuQ2xhc3MgPSBgJHtwYXJhbXMuY29udGFpbmVyTW9kaWZpZXJDbGFzc31iYWNrZmFjZS1oaWRkZW5gO1xuICAgIGNvbnN0IGhhc0NsYXNzQmFja2ZhY2VDbGFzc0FkZGVkID0gc3dpcGVyLmVsLmNsYXNzTGlzdC5jb250YWlucyhiYWNrRmFjZUhpZGRlbkNsYXNzKTtcbiAgICBpZiAoc2xpZGVzTGVuZ3RoIDw9IHBhcmFtcy5tYXhCYWNrZmFjZUhpZGRlblNsaWRlcykge1xuICAgICAgaWYgKCFoYXNDbGFzc0JhY2tmYWNlQ2xhc3NBZGRlZCkgc3dpcGVyLmVsLmNsYXNzTGlzdC5hZGQoYmFja0ZhY2VIaWRkZW5DbGFzcyk7XG4gICAgfSBlbHNlIGlmIChoYXNDbGFzc0JhY2tmYWNlQ2xhc3NBZGRlZCkge1xuICAgICAgc3dpcGVyLmVsLmNsYXNzTGlzdC5yZW1vdmUoYmFja0ZhY2VIaWRkZW5DbGFzcyk7XG4gICAgfVxuICB9XG59XG5cbmZ1bmN0aW9uIHVwZGF0ZUF1dG9IZWlnaHQoc3BlZWQpIHtcbiAgY29uc3Qgc3dpcGVyID0gdGhpcztcbiAgY29uc3QgYWN0aXZlU2xpZGVzID0gW107XG4gIGNvbnN0IGlzVmlydHVhbCA9IHN3aXBlci52aXJ0dWFsICYmIHN3aXBlci5wYXJhbXMudmlydHVhbC5lbmFibGVkO1xuICBsZXQgbmV3SGVpZ2h0ID0gMDtcbiAgbGV0IGk7XG4gIGlmICh0eXBlb2Ygc3BlZWQgPT09ICdudW1iZXInKSB7XG4gICAgc3dpcGVyLnNldFRyYW5zaXRpb24oc3BlZWQpO1xuICB9IGVsc2UgaWYgKHNwZWVkID09PSB0cnVlKSB7XG4gICAgc3dpcGVyLnNldFRyYW5zaXRpb24oc3dpcGVyLnBhcmFtcy5zcGVlZCk7XG4gIH1cbiAgY29uc3QgZ2V0U2xpZGVCeUluZGV4ID0gaW5kZXggPT4ge1xuICAgIGlmIChpc1ZpcnR1YWwpIHtcbiAgICAgIHJldHVybiBzd2lwZXIuc2xpZGVzW3N3aXBlci5nZXRTbGlkZUluZGV4QnlEYXRhKGluZGV4KV07XG4gICAgfVxuICAgIHJldHVybiBzd2lwZXIuc2xpZGVzW2luZGV4XTtcbiAgfTtcbiAgLy8gRmluZCBzbGlkZXMgY3VycmVudGx5IGluIHZpZXdcbiAgaWYgKHN3aXBlci5wYXJhbXMuc2xpZGVzUGVyVmlldyAhPT0gJ2F1dG8nICYmIHN3aXBlci5wYXJhbXMuc2xpZGVzUGVyVmlldyA+IDEpIHtcbiAgICBpZiAoc3dpcGVyLnBhcmFtcy5jZW50ZXJlZFNsaWRlcykge1xuICAgICAgKHN3aXBlci52aXNpYmxlU2xpZGVzIHx8IFtdKS5mb3JFYWNoKHNsaWRlID0+IHtcbiAgICAgICAgYWN0aXZlU2xpZGVzLnB1c2goc2xpZGUpO1xuICAgICAgfSk7XG4gICAgfSBlbHNlIHtcbiAgICAgIGZvciAoaSA9IDA7IGkgPCBNYXRoLmNlaWwoc3dpcGVyLnBhcmFtcy5zbGlkZXNQZXJWaWV3KTsgaSArPSAxKSB7XG4gICAgICAgIGNvbnN0IGluZGV4ID0gc3dpcGVyLmFjdGl2ZUluZGV4ICsgaTtcbiAgICAgICAgaWYgKGluZGV4ID4gc3dpcGVyLnNsaWRlcy5sZW5ndGggJiYgIWlzVmlydHVhbCkgYnJlYWs7XG4gICAgICAgIGFjdGl2ZVNsaWRlcy5wdXNoKGdldFNsaWRlQnlJbmRleChpbmRleCkpO1xuICAgICAgfVxuICAgIH1cbiAgfSBlbHNlIHtcbiAgICBhY3RpdmVTbGlkZXMucHVzaChnZXRTbGlkZUJ5SW5kZXgoc3dpcGVyLmFjdGl2ZUluZGV4KSk7XG4gIH1cblxuICAvLyBGaW5kIG5ldyBoZWlnaHQgZnJvbSBoaWdoZXN0IHNsaWRlIGluIHZpZXdcbiAgZm9yIChpID0gMDsgaSA8IGFjdGl2ZVNsaWRlcy5sZW5ndGg7IGkgKz0gMSkge1xuICAgIGlmICh0eXBlb2YgYWN0aXZlU2xpZGVzW2ldICE9PSAndW5kZWZpbmVkJykge1xuICAgICAgY29uc3QgaGVpZ2h0ID0gYWN0aXZlU2xpZGVzW2ldLm9mZnNldEhlaWdodDtcbiAgICAgIG5ld0hlaWdodCA9IGhlaWdodCA+IG5ld0hlaWdodCA/IGhlaWdodCA6IG5ld0hlaWdodDtcbiAgICB9XG4gIH1cblxuICAvLyBVcGRhdGUgSGVpZ2h0XG4gIGlmIChuZXdIZWlnaHQgfHwgbmV3SGVpZ2h0ID09PSAwKSBzd2lwZXIud3JhcHBlckVsLnN0eWxlLmhlaWdodCA9IGAke25ld0hlaWdodH1weGA7XG59XG5cbmZ1bmN0aW9uIHVwZGF0ZVNsaWRlc09mZnNldCgpIHtcbiAgY29uc3Qgc3dpcGVyID0gdGhpcztcbiAgY29uc3Qgc2xpZGVzID0gc3dpcGVyLnNsaWRlcztcbiAgLy8gZXNsaW50LWRpc2FibGUtbmV4dC1saW5lXG4gIGNvbnN0IG1pbnVzT2Zmc2V0ID0gc3dpcGVyLmlzRWxlbWVudCA/IHN3aXBlci5pc0hvcml6b250YWwoKSA/IHN3aXBlci53cmFwcGVyRWwub2Zmc2V0TGVmdCA6IHN3aXBlci53cmFwcGVyRWwub2Zmc2V0VG9wIDogMDtcbiAgZm9yIChsZXQgaSA9IDA7IGkgPCBzbGlkZXMubGVuZ3RoOyBpICs9IDEpIHtcbiAgICBzbGlkZXNbaV0uc3dpcGVyU2xpZGVPZmZzZXQgPSAoc3dpcGVyLmlzSG9yaXpvbnRhbCgpID8gc2xpZGVzW2ldLm9mZnNldExlZnQgOiBzbGlkZXNbaV0ub2Zmc2V0VG9wKSAtIG1pbnVzT2Zmc2V0IC0gc3dpcGVyLmNzc092ZXJmbG93QWRqdXN0bWVudCgpO1xuICB9XG59XG5cbmNvbnN0IHRvZ2dsZVNsaWRlQ2xhc3NlcyQxID0gKHNsaWRlRWwsIGNvbmRpdGlvbiwgY2xhc3NOYW1lKSA9PiB7XG4gIGlmIChjb25kaXRpb24gJiYgIXNsaWRlRWwuY2xhc3NMaXN0LmNvbnRhaW5zKGNsYXNzTmFtZSkpIHtcbiAgICBzbGlkZUVsLmNsYXNzTGlzdC5hZGQoY2xhc3NOYW1lKTtcbiAgfSBlbHNlIGlmICghY29uZGl0aW9uICYmIHNsaWRlRWwuY2xhc3NMaXN0LmNvbnRhaW5zKGNsYXNzTmFtZSkpIHtcbiAgICBzbGlkZUVsLmNsYXNzTGlzdC5yZW1vdmUoY2xhc3NOYW1lKTtcbiAgfVxufTtcbmZ1bmN0aW9uIHVwZGF0ZVNsaWRlc1Byb2dyZXNzKHRyYW5zbGF0ZSkge1xuICBpZiAodHJhbnNsYXRlID09PSB2b2lkIDApIHtcbiAgICB0cmFuc2xhdGUgPSB0aGlzICYmIHRoaXMudHJhbnNsYXRlIHx8IDA7XG4gIH1cbiAgY29uc3Qgc3dpcGVyID0gdGhpcztcbiAgY29uc3QgcGFyYW1zID0gc3dpcGVyLnBhcmFtcztcbiAgY29uc3Qge1xuICAgIHNsaWRlcyxcbiAgICBydGxUcmFuc2xhdGU6IHJ0bCxcbiAgICBzbmFwR3JpZFxuICB9ID0gc3dpcGVyO1xuICBpZiAoc2xpZGVzLmxlbmd0aCA9PT0gMCkgcmV0dXJuO1xuICBpZiAodHlwZW9mIHNsaWRlc1swXS5zd2lwZXJTbGlkZU9mZnNldCA9PT0gJ3VuZGVmaW5lZCcpIHN3aXBlci51cGRhdGVTbGlkZXNPZmZzZXQoKTtcbiAgbGV0IG9mZnNldENlbnRlciA9IC10cmFuc2xhdGU7XG4gIGlmIChydGwpIG9mZnNldENlbnRlciA9IHRyYW5zbGF0ZTtcbiAgc3dpcGVyLnZpc2libGVTbGlkZXNJbmRleGVzID0gW107XG4gIHN3aXBlci52aXNpYmxlU2xpZGVzID0gW107XG4gIGxldCBzcGFjZUJldHdlZW4gPSBwYXJhbXMuc3BhY2VCZXR3ZWVuO1xuICBpZiAodHlwZW9mIHNwYWNlQmV0d2VlbiA9PT0gJ3N0cmluZycgJiYgc3BhY2VCZXR3ZWVuLmluZGV4T2YoJyUnKSA+PSAwKSB7XG4gICAgc3BhY2VCZXR3ZWVuID0gcGFyc2VGbG9hdChzcGFjZUJldHdlZW4ucmVwbGFjZSgnJScsICcnKSkgLyAxMDAgKiBzd2lwZXIuc2l6ZTtcbiAgfSBlbHNlIGlmICh0eXBlb2Ygc3BhY2VCZXR3ZWVuID09PSAnc3RyaW5nJykge1xuICAgIHNwYWNlQmV0d2VlbiA9IHBhcnNlRmxvYXQoc3BhY2VCZXR3ZWVuKTtcbiAgfVxuICBmb3IgKGxldCBpID0gMDsgaSA8IHNsaWRlcy5sZW5ndGg7IGkgKz0gMSkge1xuICAgIGNvbnN0IHNsaWRlID0gc2xpZGVzW2ldO1xuICAgIGxldCBzbGlkZU9mZnNldCA9IHNsaWRlLnN3aXBlclNsaWRlT2Zmc2V0O1xuICAgIGlmIChwYXJhbXMuY3NzTW9kZSAmJiBwYXJhbXMuY2VudGVyZWRTbGlkZXMpIHtcbiAgICAgIHNsaWRlT2Zmc2V0IC09IHNsaWRlc1swXS5zd2lwZXJTbGlkZU9mZnNldDtcbiAgICB9XG4gICAgY29uc3Qgc2xpZGVQcm9ncmVzcyA9IChvZmZzZXRDZW50ZXIgKyAocGFyYW1zLmNlbnRlcmVkU2xpZGVzID8gc3dpcGVyLm1pblRyYW5zbGF0ZSgpIDogMCkgLSBzbGlkZU9mZnNldCkgLyAoc2xpZGUuc3dpcGVyU2xpZGVTaXplICsgc3BhY2VCZXR3ZWVuKTtcbiAgICBjb25zdCBvcmlnaW5hbFNsaWRlUHJvZ3Jlc3MgPSAob2Zmc2V0Q2VudGVyIC0gc25hcEdyaWRbMF0gKyAocGFyYW1zLmNlbnRlcmVkU2xpZGVzID8gc3dpcGVyLm1pblRyYW5zbGF0ZSgpIDogMCkgLSBzbGlkZU9mZnNldCkgLyAoc2xpZGUuc3dpcGVyU2xpZGVTaXplICsgc3BhY2VCZXR3ZWVuKTtcbiAgICBjb25zdCBzbGlkZUJlZm9yZSA9IC0ob2Zmc2V0Q2VudGVyIC0gc2xpZGVPZmZzZXQpO1xuICAgIGNvbnN0IHNsaWRlQWZ0ZXIgPSBzbGlkZUJlZm9yZSArIHN3aXBlci5zbGlkZXNTaXplc0dyaWRbaV07XG4gICAgY29uc3QgaXNGdWxseVZpc2libGUgPSBzbGlkZUJlZm9yZSA+PSAwICYmIHNsaWRlQmVmb3JlIDw9IHN3aXBlci5zaXplIC0gc3dpcGVyLnNsaWRlc1NpemVzR3JpZFtpXTtcbiAgICBjb25zdCBpc1Zpc2libGUgPSBzbGlkZUJlZm9yZSA+PSAwICYmIHNsaWRlQmVmb3JlIDwgc3dpcGVyLnNpemUgLSAxIHx8IHNsaWRlQWZ0ZXIgPiAxICYmIHNsaWRlQWZ0ZXIgPD0gc3dpcGVyLnNpemUgfHwgc2xpZGVCZWZvcmUgPD0gMCAmJiBzbGlkZUFmdGVyID49IHN3aXBlci5zaXplO1xuICAgIGlmIChpc1Zpc2libGUpIHtcbiAgICAgIHN3aXBlci52aXNpYmxlU2xpZGVzLnB1c2goc2xpZGUpO1xuICAgICAgc3dpcGVyLnZpc2libGVTbGlkZXNJbmRleGVzLnB1c2goaSk7XG4gICAgfVxuICAgIHRvZ2dsZVNsaWRlQ2xhc3NlcyQxKHNsaWRlLCBpc1Zpc2libGUsIHBhcmFtcy5zbGlkZVZpc2libGVDbGFzcyk7XG4gICAgdG9nZ2xlU2xpZGVDbGFzc2VzJDEoc2xpZGUsIGlzRnVsbHlWaXNpYmxlLCBwYXJhbXMuc2xpZGVGdWxseVZpc2libGVDbGFzcyk7XG4gICAgc2xpZGUucHJvZ3Jlc3MgPSBydGwgPyAtc2xpZGVQcm9ncmVzcyA6IHNsaWRlUHJvZ3Jlc3M7XG4gICAgc2xpZGUub3JpZ2luYWxQcm9ncmVzcyA9IHJ0bCA/IC1vcmlnaW5hbFNsaWRlUHJvZ3Jlc3MgOiBvcmlnaW5hbFNsaWRlUHJvZ3Jlc3M7XG4gIH1cbn1cblxuZnVuY3Rpb24gdXBkYXRlUHJvZ3Jlc3ModHJhbnNsYXRlKSB7XG4gIGNvbnN0IHN3aXBlciA9IHRoaXM7XG4gIGlmICh0eXBlb2YgdHJhbnNsYXRlID09PSAndW5kZWZpbmVkJykge1xuICAgIGNvbnN0IG11bHRpcGxpZXIgPSBzd2lwZXIucnRsVHJhbnNsYXRlID8gLTEgOiAxO1xuICAgIC8vIGVzbGludC1kaXNhYmxlLW5leHQtbGluZVxuICAgIHRyYW5zbGF0ZSA9IHN3aXBlciAmJiBzd2lwZXIudHJhbnNsYXRlICYmIHN3aXBlci50cmFuc2xhdGUgKiBtdWx0aXBsaWVyIHx8IDA7XG4gIH1cbiAgY29uc3QgcGFyYW1zID0gc3dpcGVyLnBhcmFtcztcbiAgY29uc3QgdHJhbnNsYXRlc0RpZmYgPSBzd2lwZXIubWF4VHJhbnNsYXRlKCkgLSBzd2lwZXIubWluVHJhbnNsYXRlKCk7XG4gIGxldCB7XG4gICAgcHJvZ3Jlc3MsXG4gICAgaXNCZWdpbm5pbmcsXG4gICAgaXNFbmQsXG4gICAgcHJvZ3Jlc3NMb29wXG4gIH0gPSBzd2lwZXI7XG4gIGNvbnN0IHdhc0JlZ2lubmluZyA9IGlzQmVnaW5uaW5nO1xuICBjb25zdCB3YXNFbmQgPSBpc0VuZDtcbiAgaWYgKHRyYW5zbGF0ZXNEaWZmID09PSAwKSB7XG4gICAgcHJvZ3Jlc3MgPSAwO1xuICAgIGlzQmVnaW5uaW5nID0gdHJ1ZTtcbiAgICBpc0VuZCA9IHRydWU7XG4gIH0gZWxzZSB7XG4gICAgcHJvZ3Jlc3MgPSAodHJhbnNsYXRlIC0gc3dpcGVyLm1pblRyYW5zbGF0ZSgpKSAvIHRyYW5zbGF0ZXNEaWZmO1xuICAgIGNvbnN0IGlzQmVnaW5uaW5nUm91bmRlZCA9IE1hdGguYWJzKHRyYW5zbGF0ZSAtIHN3aXBlci5taW5UcmFuc2xhdGUoKSkgPCAxO1xuICAgIGNvbnN0IGlzRW5kUm91bmRlZCA9IE1hdGguYWJzKHRyYW5zbGF0ZSAtIHN3aXBlci5tYXhUcmFuc2xhdGUoKSkgPCAxO1xuICAgIGlzQmVnaW5uaW5nID0gaXNCZWdpbm5pbmdSb3VuZGVkIHx8IHByb2dyZXNzIDw9IDA7XG4gICAgaXNFbmQgPSBpc0VuZFJvdW5kZWQgfHwgcHJvZ3Jlc3MgPj0gMTtcbiAgICBpZiAoaXNCZWdpbm5pbmdSb3VuZGVkKSBwcm9ncmVzcyA9IDA7XG4gICAgaWYgKGlzRW5kUm91bmRlZCkgcHJvZ3Jlc3MgPSAxO1xuICB9XG4gIGlmIChwYXJhbXMubG9vcCkge1xuICAgIGNvbnN0IGZpcnN0U2xpZGVJbmRleCA9IHN3aXBlci5nZXRTbGlkZUluZGV4QnlEYXRhKDApO1xuICAgIGNvbnN0IGxhc3RTbGlkZUluZGV4ID0gc3dpcGVyLmdldFNsaWRlSW5kZXhCeURhdGEoc3dpcGVyLnNsaWRlcy5sZW5ndGggLSAxKTtcbiAgICBjb25zdCBmaXJzdFNsaWRlVHJhbnNsYXRlID0gc3dpcGVyLnNsaWRlc0dyaWRbZmlyc3RTbGlkZUluZGV4XTtcbiAgICBjb25zdCBsYXN0U2xpZGVUcmFuc2xhdGUgPSBzd2lwZXIuc2xpZGVzR3JpZFtsYXN0U2xpZGVJbmRleF07XG4gICAgY29uc3QgdHJhbnNsYXRlTWF4ID0gc3dpcGVyLnNsaWRlc0dyaWRbc3dpcGVyLnNsaWRlc0dyaWQubGVuZ3RoIC0gMV07XG4gICAgY29uc3QgdHJhbnNsYXRlQWJzID0gTWF0aC5hYnModHJhbnNsYXRlKTtcbiAgICBpZiAodHJhbnNsYXRlQWJzID49IGZpcnN0U2xpZGVUcmFuc2xhdGUpIHtcbiAgICAgIHByb2dyZXNzTG9vcCA9ICh0cmFuc2xhdGVBYnMgLSBmaXJzdFNsaWRlVHJhbnNsYXRlKSAvIHRyYW5zbGF0ZU1heDtcbiAgICB9IGVsc2Uge1xuICAgICAgcHJvZ3Jlc3NMb29wID0gKHRyYW5zbGF0ZUFicyArIHRyYW5zbGF0ZU1heCAtIGxhc3RTbGlkZVRyYW5zbGF0ZSkgLyB0cmFuc2xhdGVNYXg7XG4gICAgfVxuICAgIGlmIChwcm9ncmVzc0xvb3AgPiAxKSBwcm9ncmVzc0xvb3AgLT0gMTtcbiAgfVxuICBPYmplY3QuYXNzaWduKHN3aXBlciwge1xuICAgIHByb2dyZXNzLFxuICAgIHByb2dyZXNzTG9vcCxcbiAgICBpc0JlZ2lubmluZyxcbiAgICBpc0VuZFxuICB9KTtcbiAgaWYgKHBhcmFtcy53YXRjaFNsaWRlc1Byb2dyZXNzIHx8IHBhcmFtcy5jZW50ZXJlZFNsaWRlcyAmJiBwYXJhbXMuYXV0b0hlaWdodCkgc3dpcGVyLnVwZGF0ZVNsaWRlc1Byb2dyZXNzKHRyYW5zbGF0ZSk7XG4gIGlmIChpc0JlZ2lubmluZyAmJiAhd2FzQmVnaW5uaW5nKSB7XG4gICAgc3dpcGVyLmVtaXQoJ3JlYWNoQmVnaW5uaW5nIHRvRWRnZScpO1xuICB9XG4gIGlmIChpc0VuZCAmJiAhd2FzRW5kKSB7XG4gICAgc3dpcGVyLmVtaXQoJ3JlYWNoRW5kIHRvRWRnZScpO1xuICB9XG4gIGlmICh3YXNCZWdpbm5pbmcgJiYgIWlzQmVnaW5uaW5nIHx8IHdhc0VuZCAmJiAhaXNFbmQpIHtcbiAgICBzd2lwZXIuZW1pdCgnZnJvbUVkZ2UnKTtcbiAgfVxuICBzd2lwZXIuZW1pdCgncHJvZ3Jlc3MnLCBwcm9ncmVzcyk7XG59XG5cbmNvbnN0IHRvZ2dsZVNsaWRlQ2xhc3NlcyA9IChzbGlkZUVsLCBjb25kaXRpb24sIGNsYXNzTmFtZSkgPT4ge1xuICBpZiAoY29uZGl0aW9uICYmICFzbGlkZUVsLmNsYXNzTGlzdC5jb250YWlucyhjbGFzc05hbWUpKSB7XG4gICAgc2xpZGVFbC5jbGFzc0xpc3QuYWRkKGNsYXNzTmFtZSk7XG4gIH0gZWxzZSBpZiAoIWNvbmRpdGlvbiAmJiBzbGlkZUVsLmNsYXNzTGlzdC5jb250YWlucyhjbGFzc05hbWUpKSB7XG4gICAgc2xpZGVFbC5jbGFzc0xpc3QucmVtb3ZlKGNsYXNzTmFtZSk7XG4gIH1cbn07XG5mdW5jdGlvbiB1cGRhdGVTbGlkZXNDbGFzc2VzKCkge1xuICBjb25zdCBzd2lwZXIgPSB0aGlzO1xuICBjb25zdCB7XG4gICAgc2xpZGVzLFxuICAgIHBhcmFtcyxcbiAgICBzbGlkZXNFbCxcbiAgICBhY3RpdmVJbmRleFxuICB9ID0gc3dpcGVyO1xuICBjb25zdCBpc1ZpcnR1YWwgPSBzd2lwZXIudmlydHVhbCAmJiBwYXJhbXMudmlydHVhbC5lbmFibGVkO1xuICBjb25zdCBncmlkRW5hYmxlZCA9IHN3aXBlci5ncmlkICYmIHBhcmFtcy5ncmlkICYmIHBhcmFtcy5ncmlkLnJvd3MgPiAxO1xuICBjb25zdCBnZXRGaWx0ZXJlZFNsaWRlID0gc2VsZWN0b3IgPT4ge1xuICAgIHJldHVybiBlbGVtZW50Q2hpbGRyZW4oc2xpZGVzRWwsIGAuJHtwYXJhbXMuc2xpZGVDbGFzc30ke3NlbGVjdG9yfSwgc3dpcGVyLXNsaWRlJHtzZWxlY3Rvcn1gKVswXTtcbiAgfTtcbiAgbGV0IGFjdGl2ZVNsaWRlO1xuICBsZXQgcHJldlNsaWRlO1xuICBsZXQgbmV4dFNsaWRlO1xuICBpZiAoaXNWaXJ0dWFsKSB7XG4gICAgaWYgKHBhcmFtcy5sb29wKSB7XG4gICAgICBsZXQgc2xpZGVJbmRleCA9IGFjdGl2ZUluZGV4IC0gc3dpcGVyLnZpcnR1YWwuc2xpZGVzQmVmb3JlO1xuICAgICAgaWYgKHNsaWRlSW5kZXggPCAwKSBzbGlkZUluZGV4ID0gc3dpcGVyLnZpcnR1YWwuc2xpZGVzLmxlbmd0aCArIHNsaWRlSW5kZXg7XG4gICAgICBpZiAoc2xpZGVJbmRleCA+PSBzd2lwZXIudmlydHVhbC5zbGlkZXMubGVuZ3RoKSBzbGlkZUluZGV4IC09IHN3aXBlci52aXJ0dWFsLnNsaWRlcy5sZW5ndGg7XG4gICAgICBhY3RpdmVTbGlkZSA9IGdldEZpbHRlcmVkU2xpZGUoYFtkYXRhLXN3aXBlci1zbGlkZS1pbmRleD1cIiR7c2xpZGVJbmRleH1cIl1gKTtcbiAgICB9IGVsc2Uge1xuICAgICAgYWN0aXZlU2xpZGUgPSBnZXRGaWx0ZXJlZFNsaWRlKGBbZGF0YS1zd2lwZXItc2xpZGUtaW5kZXg9XCIke2FjdGl2ZUluZGV4fVwiXWApO1xuICAgIH1cbiAgfSBlbHNlIHtcbiAgICBpZiAoZ3JpZEVuYWJsZWQpIHtcbiAgICAgIGFjdGl2ZVNsaWRlID0gc2xpZGVzLmZpbHRlcihzbGlkZUVsID0+IHNsaWRlRWwuY29sdW1uID09PSBhY3RpdmVJbmRleClbMF07XG4gICAgICBuZXh0U2xpZGUgPSBzbGlkZXMuZmlsdGVyKHNsaWRlRWwgPT4gc2xpZGVFbC5jb2x1bW4gPT09IGFjdGl2ZUluZGV4ICsgMSlbMF07XG4gICAgICBwcmV2U2xpZGUgPSBzbGlkZXMuZmlsdGVyKHNsaWRlRWwgPT4gc2xpZGVFbC5jb2x1bW4gPT09IGFjdGl2ZUluZGV4IC0gMSlbMF07XG4gICAgfSBlbHNlIHtcbiAgICAgIGFjdGl2ZVNsaWRlID0gc2xpZGVzW2FjdGl2ZUluZGV4XTtcbiAgICB9XG4gIH1cbiAgaWYgKGFjdGl2ZVNsaWRlKSB7XG4gICAgaWYgKCFncmlkRW5hYmxlZCkge1xuICAgICAgLy8gTmV4dCBTbGlkZVxuICAgICAgbmV4dFNsaWRlID0gZWxlbWVudE5leHRBbGwoYWN0aXZlU2xpZGUsIGAuJHtwYXJhbXMuc2xpZGVDbGFzc30sIHN3aXBlci1zbGlkZWApWzBdO1xuICAgICAgaWYgKHBhcmFtcy5sb29wICYmICFuZXh0U2xpZGUpIHtcbiAgICAgICAgbmV4dFNsaWRlID0gc2xpZGVzWzBdO1xuICAgICAgfVxuXG4gICAgICAvLyBQcmV2IFNsaWRlXG4gICAgICBwcmV2U2xpZGUgPSBlbGVtZW50UHJldkFsbChhY3RpdmVTbGlkZSwgYC4ke3BhcmFtcy5zbGlkZUNsYXNzfSwgc3dpcGVyLXNsaWRlYClbMF07XG4gICAgICBpZiAocGFyYW1zLmxvb3AgJiYgIXByZXZTbGlkZSA9PT0gMCkge1xuICAgICAgICBwcmV2U2xpZGUgPSBzbGlkZXNbc2xpZGVzLmxlbmd0aCAtIDFdO1xuICAgICAgfVxuICAgIH1cbiAgfVxuICBzbGlkZXMuZm9yRWFjaChzbGlkZUVsID0+IHtcbiAgICB0b2dnbGVTbGlkZUNsYXNzZXMoc2xpZGVFbCwgc2xpZGVFbCA9PT0gYWN0aXZlU2xpZGUsIHBhcmFtcy5zbGlkZUFjdGl2ZUNsYXNzKTtcbiAgICB0b2dnbGVTbGlkZUNsYXNzZXMoc2xpZGVFbCwgc2xpZGVFbCA9PT0gbmV4dFNsaWRlLCBwYXJhbXMuc2xpZGVOZXh0Q2xhc3MpO1xuICAgIHRvZ2dsZVNsaWRlQ2xhc3NlcyhzbGlkZUVsLCBzbGlkZUVsID09PSBwcmV2U2xpZGUsIHBhcmFtcy5zbGlkZVByZXZDbGFzcyk7XG4gIH0pO1xuICBzd2lwZXIuZW1pdFNsaWRlc0NsYXNzZXMoKTtcbn1cblxuY29uc3QgcHJvY2Vzc0xhenlQcmVsb2FkZXIgPSAoc3dpcGVyLCBpbWFnZUVsKSA9PiB7XG4gIGlmICghc3dpcGVyIHx8IHN3aXBlci5kZXN0cm95ZWQgfHwgIXN3aXBlci5wYXJhbXMpIHJldHVybjtcbiAgY29uc3Qgc2xpZGVTZWxlY3RvciA9ICgpID0+IHN3aXBlci5pc0VsZW1lbnQgPyBgc3dpcGVyLXNsaWRlYCA6IGAuJHtzd2lwZXIucGFyYW1zLnNsaWRlQ2xhc3N9YDtcbiAgY29uc3Qgc2xpZGVFbCA9IGltYWdlRWwuY2xvc2VzdChzbGlkZVNlbGVjdG9yKCkpO1xuICBpZiAoc2xpZGVFbCkge1xuICAgIGxldCBsYXp5RWwgPSBzbGlkZUVsLnF1ZXJ5U2VsZWN0b3IoYC4ke3N3aXBlci5wYXJhbXMubGF6eVByZWxvYWRlckNsYXNzfWApO1xuICAgIGlmICghbGF6eUVsICYmIHN3aXBlci5pc0VsZW1lbnQpIHtcbiAgICAgIGlmIChzbGlkZUVsLnNoYWRvd1Jvb3QpIHtcbiAgICAgICAgbGF6eUVsID0gc2xpZGVFbC5zaGFkb3dSb290LnF1ZXJ5U2VsZWN0b3IoYC4ke3N3aXBlci5wYXJhbXMubGF6eVByZWxvYWRlckNsYXNzfWApO1xuICAgICAgfSBlbHNlIHtcbiAgICAgICAgLy8gaW5pdCBsYXRlclxuICAgICAgICByZXF1ZXN0QW5pbWF0aW9uRnJhbWUoKCkgPT4ge1xuICAgICAgICAgIGlmIChzbGlkZUVsLnNoYWRvd1Jvb3QpIHtcbiAgICAgICAgICAgIGxhenlFbCA9IHNsaWRlRWwuc2hhZG93Um9vdC5xdWVyeVNlbGVjdG9yKGAuJHtzd2lwZXIucGFyYW1zLmxhenlQcmVsb2FkZXJDbGFzc31gKTtcbiAgICAgICAgICAgIGlmIChsYXp5RWwpIGxhenlFbC5yZW1vdmUoKTtcbiAgICAgICAgICB9XG4gICAgICAgIH0pO1xuICAgICAgfVxuICAgIH1cbiAgICBpZiAobGF6eUVsKSBsYXp5RWwucmVtb3ZlKCk7XG4gIH1cbn07XG5jb25zdCB1bmxhenkgPSAoc3dpcGVyLCBpbmRleCkgPT4ge1xuICBpZiAoIXN3aXBlci5zbGlkZXNbaW5kZXhdKSByZXR1cm47XG4gIGNvbnN0IGltYWdlRWwgPSBzd2lwZXIuc2xpZGVzW2luZGV4XS5xdWVyeVNlbGVjdG9yKCdbbG9hZGluZz1cImxhenlcIl0nKTtcbiAgaWYgKGltYWdlRWwpIGltYWdlRWwucmVtb3ZlQXR0cmlidXRlKCdsb2FkaW5nJyk7XG59O1xuY29uc3QgcHJlbG9hZCA9IHN3aXBlciA9PiB7XG4gIGlmICghc3dpcGVyIHx8IHN3aXBlci5kZXN0cm95ZWQgfHwgIXN3aXBlci5wYXJhbXMpIHJldHVybjtcbiAgbGV0IGFtb3VudCA9IHN3aXBlci5wYXJhbXMubGF6eVByZWxvYWRQcmV2TmV4dDtcbiAgY29uc3QgbGVuID0gc3dpcGVyLnNsaWRlcy5sZW5ndGg7XG4gIGlmICghbGVuIHx8ICFhbW91bnQgfHwgYW1vdW50IDwgMCkgcmV0dXJuO1xuICBhbW91bnQgPSBNYXRoLm1pbihhbW91bnQsIGxlbik7XG4gIGNvbnN0IHNsaWRlc1BlclZpZXcgPSBzd2lwZXIucGFyYW1zLnNsaWRlc1BlclZpZXcgPT09ICdhdXRvJyA/IHN3aXBlci5zbGlkZXNQZXJWaWV3RHluYW1pYygpIDogTWF0aC5jZWlsKHN3aXBlci5wYXJhbXMuc2xpZGVzUGVyVmlldyk7XG4gIGNvbnN0IGFjdGl2ZUluZGV4ID0gc3dpcGVyLmFjdGl2ZUluZGV4O1xuICBpZiAoc3dpcGVyLnBhcmFtcy5ncmlkICYmIHN3aXBlci5wYXJhbXMuZ3JpZC5yb3dzID4gMSkge1xuICAgIGNvbnN0IGFjdGl2ZUNvbHVtbiA9IGFjdGl2ZUluZGV4O1xuICAgIGNvbnN0IHByZWxvYWRDb2x1bW5zID0gW2FjdGl2ZUNvbHVtbiAtIGFtb3VudF07XG4gICAgcHJlbG9hZENvbHVtbnMucHVzaCguLi5BcnJheS5mcm9tKHtcbiAgICAgIGxlbmd0aDogYW1vdW50XG4gICAgfSkubWFwKChfLCBpKSA9PiB7XG4gICAgICByZXR1cm4gYWN0aXZlQ29sdW1uICsgc2xpZGVzUGVyVmlldyArIGk7XG4gICAgfSkpO1xuICAgIHN3aXBlci5zbGlkZXMuZm9yRWFjaCgoc2xpZGVFbCwgaSkgPT4ge1xuICAgICAgaWYgKHByZWxvYWRDb2x1bW5zLmluY2x1ZGVzKHNsaWRlRWwuY29sdW1uKSkgdW5sYXp5KHN3aXBlciwgaSk7XG4gICAgfSk7XG4gICAgcmV0dXJuO1xuICB9XG4gIGNvbnN0IHNsaWRlSW5kZXhMYXN0SW5WaWV3ID0gYWN0aXZlSW5kZXggKyBzbGlkZXNQZXJWaWV3IC0gMTtcbiAgaWYgKHN3aXBlci5wYXJhbXMucmV3aW5kIHx8IHN3aXBlci5wYXJhbXMubG9vcCkge1xuICAgIGZvciAobGV0IGkgPSBhY3RpdmVJbmRleCAtIGFtb3VudDsgaSA8PSBzbGlkZUluZGV4TGFzdEluVmlldyArIGFtb3VudDsgaSArPSAxKSB7XG4gICAgICBjb25zdCByZWFsSW5kZXggPSAoaSAlIGxlbiArIGxlbikgJSBsZW47XG4gICAgICBpZiAocmVhbEluZGV4IDwgYWN0aXZlSW5kZXggfHwgcmVhbEluZGV4ID4gc2xpZGVJbmRleExhc3RJblZpZXcpIHVubGF6eShzd2lwZXIsIHJlYWxJbmRleCk7XG4gICAgfVxuICB9IGVsc2Uge1xuICAgIGZvciAobGV0IGkgPSBNYXRoLm1heChhY3RpdmVJbmRleCAtIGFtb3VudCwgMCk7IGkgPD0gTWF0aC5taW4oc2xpZGVJbmRleExhc3RJblZpZXcgKyBhbW91bnQsIGxlbiAtIDEpOyBpICs9IDEpIHtcbiAgICAgIGlmIChpICE9PSBhY3RpdmVJbmRleCAmJiAoaSA+IHNsaWRlSW5kZXhMYXN0SW5WaWV3IHx8IGkgPCBhY3RpdmVJbmRleCkpIHtcbiAgICAgICAgdW5sYXp5KHN3aXBlciwgaSk7XG4gICAgICB9XG4gICAgfVxuICB9XG59O1xuXG5mdW5jdGlvbiBnZXRBY3RpdmVJbmRleEJ5VHJhbnNsYXRlKHN3aXBlcikge1xuICBjb25zdCB7XG4gICAgc2xpZGVzR3JpZCxcbiAgICBwYXJhbXNcbiAgfSA9IHN3aXBlcjtcbiAgY29uc3QgdHJhbnNsYXRlID0gc3dpcGVyLnJ0bFRyYW5zbGF0ZSA/IHN3aXBlci50cmFuc2xhdGUgOiAtc3dpcGVyLnRyYW5zbGF0ZTtcbiAgbGV0IGFjdGl2ZUluZGV4O1xuICBmb3IgKGxldCBpID0gMDsgaSA8IHNsaWRlc0dyaWQubGVuZ3RoOyBpICs9IDEpIHtcbiAgICBpZiAodHlwZW9mIHNsaWRlc0dyaWRbaSArIDFdICE9PSAndW5kZWZpbmVkJykge1xuICAgICAgaWYgKHRyYW5zbGF0ZSA+PSBzbGlkZXNHcmlkW2ldICYmIHRyYW5zbGF0ZSA8IHNsaWRlc0dyaWRbaSArIDFdIC0gKHNsaWRlc0dyaWRbaSArIDFdIC0gc2xpZGVzR3JpZFtpXSkgLyAyKSB7XG4gICAgICAgIGFjdGl2ZUluZGV4ID0gaTtcbiAgICAgIH0gZWxzZSBpZiAodHJhbnNsYXRlID49IHNsaWRlc0dyaWRbaV0gJiYgdHJhbnNsYXRlIDwgc2xpZGVzR3JpZFtpICsgMV0pIHtcbiAgICAgICAgYWN0aXZlSW5kZXggPSBpICsgMTtcbiAgICAgIH1cbiAgICB9IGVsc2UgaWYgKHRyYW5zbGF0ZSA+PSBzbGlkZXNHcmlkW2ldKSB7XG4gICAgICBhY3RpdmVJbmRleCA9IGk7XG4gICAgfVxuICB9XG4gIC8vIE5vcm1hbGl6ZSBzbGlkZUluZGV4XG4gIGlmIChwYXJhbXMubm9ybWFsaXplU2xpZGVJbmRleCkge1xuICAgIGlmIChhY3RpdmVJbmRleCA8IDAgfHwgdHlwZW9mIGFjdGl2ZUluZGV4ID09PSAndW5kZWZpbmVkJykgYWN0aXZlSW5kZXggPSAwO1xuICB9XG4gIHJldHVybiBhY3RpdmVJbmRleDtcbn1cbmZ1bmN0aW9uIHVwZGF0ZUFjdGl2ZUluZGV4KG5ld0FjdGl2ZUluZGV4KSB7XG4gIGNvbnN0IHN3aXBlciA9IHRoaXM7XG4gIGNvbnN0IHRyYW5zbGF0ZSA9IHN3aXBlci5ydGxUcmFuc2xhdGUgPyBzd2lwZXIudHJhbnNsYXRlIDogLXN3aXBlci50cmFuc2xhdGU7XG4gIGNvbnN0IHtcbiAgICBzbmFwR3JpZCxcbiAgICBwYXJhbXMsXG4gICAgYWN0aXZlSW5kZXg6IHByZXZpb3VzSW5kZXgsXG4gICAgcmVhbEluZGV4OiBwcmV2aW91c1JlYWxJbmRleCxcbiAgICBzbmFwSW5kZXg6IHByZXZpb3VzU25hcEluZGV4XG4gIH0gPSBzd2lwZXI7XG4gIGxldCBhY3RpdmVJbmRleCA9IG5ld0FjdGl2ZUluZGV4O1xuICBsZXQgc25hcEluZGV4O1xuICBjb25zdCBnZXRWaXJ0dWFsUmVhbEluZGV4ID0gYUluZGV4ID0+IHtcbiAgICBsZXQgcmVhbEluZGV4ID0gYUluZGV4IC0gc3dpcGVyLnZpcnR1YWwuc2xpZGVzQmVmb3JlO1xuICAgIGlmIChyZWFsSW5kZXggPCAwKSB7XG4gICAgICByZWFsSW5kZXggPSBzd2lwZXIudmlydHVhbC5zbGlkZXMubGVuZ3RoICsgcmVhbEluZGV4O1xuICAgIH1cbiAgICBpZiAocmVhbEluZGV4ID49IHN3aXBlci52aXJ0dWFsLnNsaWRlcy5sZW5ndGgpIHtcbiAgICAgIHJlYWxJbmRleCAtPSBzd2lwZXIudmlydHVhbC5zbGlkZXMubGVuZ3RoO1xuICAgIH1cbiAgICByZXR1cm4gcmVhbEluZGV4O1xuICB9O1xuICBpZiAodHlwZW9mIGFjdGl2ZUluZGV4ID09PSAndW5kZWZpbmVkJykge1xuICAgIGFjdGl2ZUluZGV4ID0gZ2V0QWN0aXZlSW5kZXhCeVRyYW5zbGF0ZShzd2lwZXIpO1xuICB9XG4gIGlmIChzbmFwR3JpZC5pbmRleE9mKHRyYW5zbGF0ZSkgPj0gMCkge1xuICAgIHNuYXBJbmRleCA9IHNuYXBHcmlkLmluZGV4T2YodHJhbnNsYXRlKTtcbiAgfSBlbHNlIHtcbiAgICBjb25zdCBza2lwID0gTWF0aC5taW4ocGFyYW1zLnNsaWRlc1Blckdyb3VwU2tpcCwgYWN0aXZlSW5kZXgpO1xuICAgIHNuYXBJbmRleCA9IHNraXAgKyBNYXRoLmZsb29yKChhY3RpdmVJbmRleCAtIHNraXApIC8gcGFyYW1zLnNsaWRlc1Blckdyb3VwKTtcbiAgfVxuICBpZiAoc25hcEluZGV4ID49IHNuYXBHcmlkLmxlbmd0aCkgc25hcEluZGV4ID0gc25hcEdyaWQubGVuZ3RoIC0gMTtcbiAgaWYgKGFjdGl2ZUluZGV4ID09PSBwcmV2aW91c0luZGV4ICYmICFzd2lwZXIucGFyYW1zLmxvb3ApIHtcbiAgICBpZiAoc25hcEluZGV4ICE9PSBwcmV2aW91c1NuYXBJbmRleCkge1xuICAgICAgc3dpcGVyLnNuYXBJbmRleCA9IHNuYXBJbmRleDtcbiAgICAgIHN3aXBlci5lbWl0KCdzbmFwSW5kZXhDaGFuZ2UnKTtcbiAgICB9XG4gICAgcmV0dXJuO1xuICB9XG4gIGlmIChhY3RpdmVJbmRleCA9PT0gcHJldmlvdXNJbmRleCAmJiBzd2lwZXIucGFyYW1zLmxvb3AgJiYgc3dpcGVyLnZpcnR1YWwgJiYgc3dpcGVyLnBhcmFtcy52aXJ0dWFsLmVuYWJsZWQpIHtcbiAgICBzd2lwZXIucmVhbEluZGV4ID0gZ2V0VmlydHVhbFJlYWxJbmRleChhY3RpdmVJbmRleCk7XG4gICAgcmV0dXJuO1xuICB9XG4gIGNvbnN0IGdyaWRFbmFibGVkID0gc3dpcGVyLmdyaWQgJiYgcGFyYW1zLmdyaWQgJiYgcGFyYW1zLmdyaWQucm93cyA+IDE7XG5cbiAgLy8gR2V0IHJlYWwgaW5kZXhcbiAgbGV0IHJlYWxJbmRleDtcbiAgaWYgKHN3aXBlci52aXJ0dWFsICYmIHBhcmFtcy52aXJ0dWFsLmVuYWJsZWQgJiYgcGFyYW1zLmxvb3ApIHtcbiAgICByZWFsSW5kZXggPSBnZXRWaXJ0dWFsUmVhbEluZGV4KGFjdGl2ZUluZGV4KTtcbiAgfSBlbHNlIGlmIChncmlkRW5hYmxlZCkge1xuICAgIGNvbnN0IGZpcnN0U2xpZGVJbkNvbHVtbiA9IHN3aXBlci5zbGlkZXMuZmlsdGVyKHNsaWRlRWwgPT4gc2xpZGVFbC5jb2x1bW4gPT09IGFjdGl2ZUluZGV4KVswXTtcbiAgICBsZXQgYWN0aXZlU2xpZGVJbmRleCA9IHBhcnNlSW50KGZpcnN0U2xpZGVJbkNvbHVtbi5nZXRBdHRyaWJ1dGUoJ2RhdGEtc3dpcGVyLXNsaWRlLWluZGV4JyksIDEwKTtcbiAgICBpZiAoTnVtYmVyLmlzTmFOKGFjdGl2ZVNsaWRlSW5kZXgpKSB7XG4gICAgICBhY3RpdmVTbGlkZUluZGV4ID0gTWF0aC5tYXgoc3dpcGVyLnNsaWRlcy5pbmRleE9mKGZpcnN0U2xpZGVJbkNvbHVtbiksIDApO1xuICAgIH1cbiAgICByZWFsSW5kZXggPSBNYXRoLmZsb29yKGFjdGl2ZVNsaWRlSW5kZXggLyBwYXJhbXMuZ3JpZC5yb3dzKTtcbiAgfSBlbHNlIGlmIChzd2lwZXIuc2xpZGVzW2FjdGl2ZUluZGV4XSkge1xuICAgIGNvbnN0IHNsaWRlSW5kZXggPSBzd2lwZXIuc2xpZGVzW2FjdGl2ZUluZGV4XS5nZXRBdHRyaWJ1dGUoJ2RhdGEtc3dpcGVyLXNsaWRlLWluZGV4Jyk7XG4gICAgaWYgKHNsaWRlSW5kZXgpIHtcbiAgICAgIHJlYWxJbmRleCA9IHBhcnNlSW50KHNsaWRlSW5kZXgsIDEwKTtcbiAgICB9IGVsc2Uge1xuICAgICAgcmVhbEluZGV4ID0gYWN0aXZlSW5kZXg7XG4gICAgfVxuICB9IGVsc2Uge1xuICAgIHJlYWxJbmRleCA9IGFjdGl2ZUluZGV4O1xuICB9XG4gIE9iamVjdC5hc3NpZ24oc3dpcGVyLCB7XG4gICAgcHJldmlvdXNTbmFwSW5kZXgsXG4gICAgc25hcEluZGV4LFxuICAgIHByZXZpb3VzUmVhbEluZGV4LFxuICAgIHJlYWxJbmRleCxcbiAgICBwcmV2aW91c0luZGV4LFxuICAgIGFjdGl2ZUluZGV4XG4gIH0pO1xuICBpZiAoc3dpcGVyLmluaXRpYWxpemVkKSB7XG4gICAgcHJlbG9hZChzd2lwZXIpO1xuICB9XG4gIHN3aXBlci5lbWl0KCdhY3RpdmVJbmRleENoYW5nZScpO1xuICBzd2lwZXIuZW1pdCgnc25hcEluZGV4Q2hhbmdlJyk7XG4gIGlmIChzd2lwZXIuaW5pdGlhbGl6ZWQgfHwgc3dpcGVyLnBhcmFtcy5ydW5DYWxsYmFja3NPbkluaXQpIHtcbiAgICBpZiAocHJldmlvdXNSZWFsSW5kZXggIT09IHJlYWxJbmRleCkge1xuICAgICAgc3dpcGVyLmVtaXQoJ3JlYWxJbmRleENoYW5nZScpO1xuICAgIH1cbiAgICBzd2lwZXIuZW1pdCgnc2xpZGVDaGFuZ2UnKTtcbiAgfVxufVxuXG5mdW5jdGlvbiB1cGRhdGVDbGlja2VkU2xpZGUoZWwsIHBhdGgpIHtcbiAgY29uc3Qgc3dpcGVyID0gdGhpcztcbiAgY29uc3QgcGFyYW1zID0gc3dpcGVyLnBhcmFtcztcbiAgbGV0IHNsaWRlID0gZWwuY2xvc2VzdChgLiR7cGFyYW1zLnNsaWRlQ2xhc3N9LCBzd2lwZXItc2xpZGVgKTtcbiAgaWYgKCFzbGlkZSAmJiBzd2lwZXIuaXNFbGVtZW50ICYmIHBhdGggJiYgcGF0aC5sZW5ndGggPiAxICYmIHBhdGguaW5jbHVkZXMoZWwpKSB7XG4gICAgWy4uLnBhdGguc2xpY2UocGF0aC5pbmRleE9mKGVsKSArIDEsIHBhdGgubGVuZ3RoKV0uZm9yRWFjaChwYXRoRWwgPT4ge1xuICAgICAgaWYgKCFzbGlkZSAmJiBwYXRoRWwubWF0Y2hlcyAmJiBwYXRoRWwubWF0Y2hlcyhgLiR7cGFyYW1zLnNsaWRlQ2xhc3N9LCBzd2lwZXItc2xpZGVgKSkge1xuICAgICAgICBzbGlkZSA9IHBhdGhFbDtcbiAgICAgIH1cbiAgICB9KTtcbiAgfVxuICBsZXQgc2xpZGVGb3VuZCA9IGZhbHNlO1xuICBsZXQgc2xpZGVJbmRleDtcbiAgaWYgKHNsaWRlKSB7XG4gICAgZm9yIChsZXQgaSA9IDA7IGkgPCBzd2lwZXIuc2xpZGVzLmxlbmd0aDsgaSArPSAxKSB7XG4gICAgICBpZiAoc3dpcGVyLnNsaWRlc1tpXSA9PT0gc2xpZGUpIHtcbiAgICAgICAgc2xpZGVGb3VuZCA9IHRydWU7XG4gICAgICAgIHNsaWRlSW5kZXggPSBpO1xuICAgICAgICBicmVhaztcbiAgICAgIH1cbiAgICB9XG4gIH1cbiAgaWYgKHNsaWRlICYmIHNsaWRlRm91bmQpIHtcbiAgICBzd2lwZXIuY2xpY2tlZFNsaWRlID0gc2xpZGU7XG4gICAgaWYgKHN3aXBlci52aXJ0dWFsICYmIHN3aXBlci5wYXJhbXMudmlydHVhbC5lbmFibGVkKSB7XG4gICAgICBzd2lwZXIuY2xpY2tlZEluZGV4ID0gcGFyc2VJbnQoc2xpZGUuZ2V0QXR0cmlidXRlKCdkYXRhLXN3aXBlci1zbGlkZS1pbmRleCcpLCAxMCk7XG4gICAgfSBlbHNlIHtcbiAgICAgIHN3aXBlci5jbGlja2VkSW5kZXggPSBzbGlkZUluZGV4O1xuICAgIH1cbiAgfSBlbHNlIHtcbiAgICBzd2lwZXIuY2xpY2tlZFNsaWRlID0gdW5kZWZpbmVkO1xuICAgIHN3aXBlci5jbGlja2VkSW5kZXggPSB1bmRlZmluZWQ7XG4gICAgcmV0dXJuO1xuICB9XG4gIGlmIChwYXJhbXMuc2xpZGVUb0NsaWNrZWRTbGlkZSAmJiBzd2lwZXIuY2xpY2tlZEluZGV4ICE9PSB1bmRlZmluZWQgJiYgc3dpcGVyLmNsaWNrZWRJbmRleCAhPT0gc3dpcGVyLmFjdGl2ZUluZGV4KSB7XG4gICAgc3dpcGVyLnNsaWRlVG9DbGlja2VkU2xpZGUoKTtcbiAgfVxufVxuXG52YXIgdXBkYXRlID0ge1xuICB1cGRhdGVTaXplLFxuICB1cGRhdGVTbGlkZXMsXG4gIHVwZGF0ZUF1dG9IZWlnaHQsXG4gIHVwZGF0ZVNsaWRlc09mZnNldCxcbiAgdXBkYXRlU2xpZGVzUHJvZ3Jlc3MsXG4gIHVwZGF0ZVByb2dyZXNzLFxuICB1cGRhdGVTbGlkZXNDbGFzc2VzLFxuICB1cGRhdGVBY3RpdmVJbmRleCxcbiAgdXBkYXRlQ2xpY2tlZFNsaWRlXG59O1xuXG5mdW5jdGlvbiBnZXRTd2lwZXJUcmFuc2xhdGUoYXhpcykge1xuICBpZiAoYXhpcyA9PT0gdm9pZCAwKSB7XG4gICAgYXhpcyA9IHRoaXMuaXNIb3Jpem9udGFsKCkgPyAneCcgOiAneSc7XG4gIH1cbiAgY29uc3Qgc3dpcGVyID0gdGhpcztcbiAgY29uc3Qge1xuICAgIHBhcmFtcyxcbiAgICBydGxUcmFuc2xhdGU6IHJ0bCxcbiAgICB0cmFuc2xhdGUsXG4gICAgd3JhcHBlckVsXG4gIH0gPSBzd2lwZXI7XG4gIGlmIChwYXJhbXMudmlydHVhbFRyYW5zbGF0ZSkge1xuICAgIHJldHVybiBydGwgPyAtdHJhbnNsYXRlIDogdHJhbnNsYXRlO1xuICB9XG4gIGlmIChwYXJhbXMuY3NzTW9kZSkge1xuICAgIHJldHVybiB0cmFuc2xhdGU7XG4gIH1cbiAgbGV0IGN1cnJlbnRUcmFuc2xhdGUgPSBnZXRUcmFuc2xhdGUod3JhcHBlckVsLCBheGlzKTtcbiAgY3VycmVudFRyYW5zbGF0ZSArPSBzd2lwZXIuY3NzT3ZlcmZsb3dBZGp1c3RtZW50KCk7XG4gIGlmIChydGwpIGN1cnJlbnRUcmFuc2xhdGUgPSAtY3VycmVudFRyYW5zbGF0ZTtcbiAgcmV0dXJuIGN1cnJlbnRUcmFuc2xhdGUgfHwgMDtcbn1cblxuZnVuY3Rpb24gc2V0VHJhbnNsYXRlKHRyYW5zbGF0ZSwgYnlDb250cm9sbGVyKSB7XG4gIGNvbnN0IHN3aXBlciA9IHRoaXM7XG4gIGNvbnN0IHtcbiAgICBydGxUcmFuc2xhdGU6IHJ0bCxcbiAgICBwYXJhbXMsXG4gICAgd3JhcHBlckVsLFxuICAgIHByb2dyZXNzXG4gIH0gPSBzd2lwZXI7XG4gIGxldCB4ID0gMDtcbiAgbGV0IHkgPSAwO1xuICBjb25zdCB6ID0gMDtcbiAgaWYgKHN3aXBlci5pc0hvcml6b250YWwoKSkge1xuICAgIHggPSBydGwgPyAtdHJhbnNsYXRlIDogdHJhbnNsYXRlO1xuICB9IGVsc2Uge1xuICAgIHkgPSB0cmFuc2xhdGU7XG4gIH1cbiAgaWYgKHBhcmFtcy5yb3VuZExlbmd0aHMpIHtcbiAgICB4ID0gTWF0aC5mbG9vcih4KTtcbiAgICB5ID0gTWF0aC5mbG9vcih5KTtcbiAgfVxuICBzd2lwZXIucHJldmlvdXNUcmFuc2xhdGUgPSBzd2lwZXIudHJhbnNsYXRlO1xuICBzd2lwZXIudHJhbnNsYXRlID0gc3dpcGVyLmlzSG9yaXpvbnRhbCgpID8geCA6IHk7XG4gIGlmIChwYXJhbXMuY3NzTW9kZSkge1xuICAgIHdyYXBwZXJFbFtzd2lwZXIuaXNIb3Jpem9udGFsKCkgPyAnc2Nyb2xsTGVmdCcgOiAnc2Nyb2xsVG9wJ10gPSBzd2lwZXIuaXNIb3Jpem9udGFsKCkgPyAteCA6IC15O1xuICB9IGVsc2UgaWYgKCFwYXJhbXMudmlydHVhbFRyYW5zbGF0ZSkge1xuICAgIGlmIChzd2lwZXIuaXNIb3Jpem9udGFsKCkpIHtcbiAgICAgIHggLT0gc3dpcGVyLmNzc092ZXJmbG93QWRqdXN0bWVudCgpO1xuICAgIH0gZWxzZSB7XG4gICAgICB5IC09IHN3aXBlci5jc3NPdmVyZmxvd0FkanVzdG1lbnQoKTtcbiAgICB9XG4gICAgd3JhcHBlckVsLnN0eWxlLnRyYW5zZm9ybSA9IGB0cmFuc2xhdGUzZCgke3h9cHgsICR7eX1weCwgJHt6fXB4KWA7XG4gIH1cblxuICAvLyBDaGVjayBpZiB3ZSBuZWVkIHRvIHVwZGF0ZSBwcm9ncmVzc1xuICBsZXQgbmV3UHJvZ3Jlc3M7XG4gIGNvbnN0IHRyYW5zbGF0ZXNEaWZmID0gc3dpcGVyLm1heFRyYW5zbGF0ZSgpIC0gc3dpcGVyLm1pblRyYW5zbGF0ZSgpO1xuICBpZiAodHJhbnNsYXRlc0RpZmYgPT09IDApIHtcbiAgICBuZXdQcm9ncmVzcyA9IDA7XG4gIH0gZWxzZSB7XG4gICAgbmV3UHJvZ3Jlc3MgPSAodHJhbnNsYXRlIC0gc3dpcGVyLm1pblRyYW5zbGF0ZSgpKSAvIHRyYW5zbGF0ZXNEaWZmO1xuICB9XG4gIGlmIChuZXdQcm9ncmVzcyAhPT0gcHJvZ3Jlc3MpIHtcbiAgICBzd2lwZXIudXBkYXRlUHJvZ3Jlc3ModHJhbnNsYXRlKTtcbiAgfVxuICBzd2lwZXIuZW1pdCgnc2V0VHJhbnNsYXRlJywgc3dpcGVyLnRyYW5zbGF0ZSwgYnlDb250cm9sbGVyKTtcbn1cblxuZnVuY3Rpb24gbWluVHJhbnNsYXRlKCkge1xuICByZXR1cm4gLXRoaXMuc25hcEdyaWRbMF07XG59XG5cbmZ1bmN0aW9uIG1heFRyYW5zbGF0ZSgpIHtcbiAgcmV0dXJuIC10aGlzLnNuYXBHcmlkW3RoaXMuc25hcEdyaWQubGVuZ3RoIC0gMV07XG59XG5cbmZ1bmN0aW9uIHRyYW5zbGF0ZVRvKHRyYW5zbGF0ZSwgc3BlZWQsIHJ1bkNhbGxiYWNrcywgdHJhbnNsYXRlQm91bmRzLCBpbnRlcm5hbCkge1xuICBpZiAodHJhbnNsYXRlID09PSB2b2lkIDApIHtcbiAgICB0cmFuc2xhdGUgPSAwO1xuICB9XG4gIGlmIChzcGVlZCA9PT0gdm9pZCAwKSB7XG4gICAgc3BlZWQgPSB0aGlzLnBhcmFtcy5zcGVlZDtcbiAgfVxuICBpZiAocnVuQ2FsbGJhY2tzID09PSB2b2lkIDApIHtcbiAgICBydW5DYWxsYmFja3MgPSB0cnVlO1xuICB9XG4gIGlmICh0cmFuc2xhdGVCb3VuZHMgPT09IHZvaWQgMCkge1xuICAgIHRyYW5zbGF0ZUJvdW5kcyA9IHRydWU7XG4gIH1cbiAgY29uc3Qgc3dpcGVyID0gdGhpcztcbiAgY29uc3Qge1xuICAgIHBhcmFtcyxcbiAgICB3cmFwcGVyRWxcbiAgfSA9IHN3aXBlcjtcbiAgaWYgKHN3aXBlci5hbmltYXRpbmcgJiYgcGFyYW1zLnByZXZlbnRJbnRlcmFjdGlvbk9uVHJhbnNpdGlvbikge1xuICAgIHJldHVybiBmYWxzZTtcbiAgfVxuICBjb25zdCBtaW5UcmFuc2xhdGUgPSBzd2lwZXIubWluVHJhbnNsYXRlKCk7XG4gIGNvbnN0IG1heFRyYW5zbGF0ZSA9IHN3aXBlci5tYXhUcmFuc2xhdGUoKTtcbiAgbGV0IG5ld1RyYW5zbGF0ZTtcbiAgaWYgKHRyYW5zbGF0ZUJvdW5kcyAmJiB0cmFuc2xhdGUgPiBtaW5UcmFuc2xhdGUpIG5ld1RyYW5zbGF0ZSA9IG1pblRyYW5zbGF0ZTtlbHNlIGlmICh0cmFuc2xhdGVCb3VuZHMgJiYgdHJhbnNsYXRlIDwgbWF4VHJhbnNsYXRlKSBuZXdUcmFuc2xhdGUgPSBtYXhUcmFuc2xhdGU7ZWxzZSBuZXdUcmFuc2xhdGUgPSB0cmFuc2xhdGU7XG5cbiAgLy8gVXBkYXRlIHByb2dyZXNzXG4gIHN3aXBlci51cGRhdGVQcm9ncmVzcyhuZXdUcmFuc2xhdGUpO1xuICBpZiAocGFyYW1zLmNzc01vZGUpIHtcbiAgICBjb25zdCBpc0ggPSBzd2lwZXIuaXNIb3Jpem9udGFsKCk7XG4gICAgaWYgKHNwZWVkID09PSAwKSB7XG4gICAgICB3cmFwcGVyRWxbaXNIID8gJ3Njcm9sbExlZnQnIDogJ3Njcm9sbFRvcCddID0gLW5ld1RyYW5zbGF0ZTtcbiAgICB9IGVsc2Uge1xuICAgICAgaWYgKCFzd2lwZXIuc3VwcG9ydC5zbW9vdGhTY3JvbGwpIHtcbiAgICAgICAgYW5pbWF0ZUNTU01vZGVTY3JvbGwoe1xuICAgICAgICAgIHN3aXBlcixcbiAgICAgICAgICB0YXJnZXRQb3NpdGlvbjogLW5ld1RyYW5zbGF0ZSxcbiAgICAgICAgICBzaWRlOiBpc0ggPyAnbGVmdCcgOiAndG9wJ1xuICAgICAgICB9KTtcbiAgICAgICAgcmV0dXJuIHRydWU7XG4gICAgICB9XG4gICAgICB3cmFwcGVyRWwuc2Nyb2xsVG8oe1xuICAgICAgICBbaXNIID8gJ2xlZnQnIDogJ3RvcCddOiAtbmV3VHJhbnNsYXRlLFxuICAgICAgICBiZWhhdmlvcjogJ3Ntb290aCdcbiAgICAgIH0pO1xuICAgIH1cbiAgICByZXR1cm4gdHJ1ZTtcbiAgfVxuICBpZiAoc3BlZWQgPT09IDApIHtcbiAgICBzd2lwZXIuc2V0VHJhbnNpdGlvbigwKTtcbiAgICBzd2lwZXIuc2V0VHJhbnNsYXRlKG5ld1RyYW5zbGF0ZSk7XG4gICAgaWYgKHJ1bkNhbGxiYWNrcykge1xuICAgICAgc3dpcGVyLmVtaXQoJ2JlZm9yZVRyYW5zaXRpb25TdGFydCcsIHNwZWVkLCBpbnRlcm5hbCk7XG4gICAgICBzd2lwZXIuZW1pdCgndHJhbnNpdGlvbkVuZCcpO1xuICAgIH1cbiAgfSBlbHNlIHtcbiAgICBzd2lwZXIuc2V0VHJhbnNpdGlvbihzcGVlZCk7XG4gICAgc3dpcGVyLnNldFRyYW5zbGF0ZShuZXdUcmFuc2xhdGUpO1xuICAgIGlmIChydW5DYWxsYmFja3MpIHtcbiAgICAgIHN3aXBlci5lbWl0KCdiZWZvcmVUcmFuc2l0aW9uU3RhcnQnLCBzcGVlZCwgaW50ZXJuYWwpO1xuICAgICAgc3dpcGVyLmVtaXQoJ3RyYW5zaXRpb25TdGFydCcpO1xuICAgIH1cbiAgICBpZiAoIXN3aXBlci5hbmltYXRpbmcpIHtcbiAgICAgIHN3aXBlci5hbmltYXRpbmcgPSB0cnVlO1xuICAgICAgaWYgKCFzd2lwZXIub25UcmFuc2xhdGVUb1dyYXBwZXJUcmFuc2l0aW9uRW5kKSB7XG4gICAgICAgIHN3aXBlci5vblRyYW5zbGF0ZVRvV3JhcHBlclRyYW5zaXRpb25FbmQgPSBmdW5jdGlvbiB0cmFuc2l0aW9uRW5kKGUpIHtcbiAgICAgICAgICBpZiAoIXN3aXBlciB8fCBzd2lwZXIuZGVzdHJveWVkKSByZXR1cm47XG4gICAgICAgICAgaWYgKGUudGFyZ2V0ICE9PSB0aGlzKSByZXR1cm47XG4gICAgICAgICAgc3dpcGVyLndyYXBwZXJFbC5yZW1vdmVFdmVudExpc3RlbmVyKCd0cmFuc2l0aW9uZW5kJywgc3dpcGVyLm9uVHJhbnNsYXRlVG9XcmFwcGVyVHJhbnNpdGlvbkVuZCk7XG4gICAgICAgICAgc3dpcGVyLm9uVHJhbnNsYXRlVG9XcmFwcGVyVHJhbnNpdGlvbkVuZCA9IG51bGw7XG4gICAgICAgICAgZGVsZXRlIHN3aXBlci5vblRyYW5zbGF0ZVRvV3JhcHBlclRyYW5zaXRpb25FbmQ7XG4gICAgICAgICAgc3dpcGVyLmFuaW1hdGluZyA9IGZhbHNlO1xuICAgICAgICAgIGlmIChydW5DYWxsYmFja3MpIHtcbiAgICAgICAgICAgIHN3aXBlci5lbWl0KCd0cmFuc2l0aW9uRW5kJyk7XG4gICAgICAgICAgfVxuICAgICAgICB9O1xuICAgICAgfVxuICAgICAgc3dpcGVyLndyYXBwZXJFbC5hZGRFdmVudExpc3RlbmVyKCd0cmFuc2l0aW9uZW5kJywgc3dpcGVyLm9uVHJhbnNsYXRlVG9XcmFwcGVyVHJhbnNpdGlvbkVuZCk7XG4gICAgfVxuICB9XG4gIHJldHVybiB0cnVlO1xufVxuXG52YXIgdHJhbnNsYXRlID0ge1xuICBnZXRUcmFuc2xhdGU6IGdldFN3aXBlclRyYW5zbGF0ZSxcbiAgc2V0VHJhbnNsYXRlLFxuICBtaW5UcmFuc2xhdGUsXG4gIG1heFRyYW5zbGF0ZSxcbiAgdHJhbnNsYXRlVG9cbn07XG5cbmZ1bmN0aW9uIHNldFRyYW5zaXRpb24oZHVyYXRpb24sIGJ5Q29udHJvbGxlcikge1xuICBjb25zdCBzd2lwZXIgPSB0aGlzO1xuICBpZiAoIXN3aXBlci5wYXJhbXMuY3NzTW9kZSkge1xuICAgIHN3aXBlci53cmFwcGVyRWwuc3R5bGUudHJhbnNpdGlvbkR1cmF0aW9uID0gYCR7ZHVyYXRpb259bXNgO1xuICAgIHN3aXBlci53cmFwcGVyRWwuc3R5bGUudHJhbnNpdGlvbkRlbGF5ID0gZHVyYXRpb24gPT09IDAgPyBgMG1zYCA6ICcnO1xuICB9XG4gIHN3aXBlci5lbWl0KCdzZXRUcmFuc2l0aW9uJywgZHVyYXRpb24sIGJ5Q29udHJvbGxlcik7XG59XG5cbmZ1bmN0aW9uIHRyYW5zaXRpb25FbWl0KF9yZWYpIHtcbiAgbGV0IHtcbiAgICBzd2lwZXIsXG4gICAgcnVuQ2FsbGJhY2tzLFxuICAgIGRpcmVjdGlvbixcbiAgICBzdGVwXG4gIH0gPSBfcmVmO1xuICBjb25zdCB7XG4gICAgYWN0aXZlSW5kZXgsXG4gICAgcHJldmlvdXNJbmRleFxuICB9ID0gc3dpcGVyO1xuICBsZXQgZGlyID0gZGlyZWN0aW9uO1xuICBpZiAoIWRpcikge1xuICAgIGlmIChhY3RpdmVJbmRleCA+IHByZXZpb3VzSW5kZXgpIGRpciA9ICduZXh0JztlbHNlIGlmIChhY3RpdmVJbmRleCA8IHByZXZpb3VzSW5kZXgpIGRpciA9ICdwcmV2JztlbHNlIGRpciA9ICdyZXNldCc7XG4gIH1cbiAgc3dpcGVyLmVtaXQoYHRyYW5zaXRpb24ke3N0ZXB9YCk7XG4gIGlmIChydW5DYWxsYmFja3MgJiYgYWN0aXZlSW5kZXggIT09IHByZXZpb3VzSW5kZXgpIHtcbiAgICBpZiAoZGlyID09PSAncmVzZXQnKSB7XG4gICAgICBzd2lwZXIuZW1pdChgc2xpZGVSZXNldFRyYW5zaXRpb24ke3N0ZXB9YCk7XG4gICAgICByZXR1cm47XG4gICAgfVxuICAgIHN3aXBlci5lbWl0KGBzbGlkZUNoYW5nZVRyYW5zaXRpb24ke3N0ZXB9YCk7XG4gICAgaWYgKGRpciA9PT0gJ25leHQnKSB7XG4gICAgICBzd2lwZXIuZW1pdChgc2xpZGVOZXh0VHJhbnNpdGlvbiR7c3RlcH1gKTtcbiAgICB9IGVsc2Uge1xuICAgICAgc3dpcGVyLmVtaXQoYHNsaWRlUHJldlRyYW5zaXRpb24ke3N0ZXB9YCk7XG4gICAgfVxuICB9XG59XG5cbmZ1bmN0aW9uIHRyYW5zaXRpb25TdGFydChydW5DYWxsYmFja3MsIGRpcmVjdGlvbikge1xuICBpZiAocnVuQ2FsbGJhY2tzID09PSB2b2lkIDApIHtcbiAgICBydW5DYWxsYmFja3MgPSB0cnVlO1xuICB9XG4gIGNvbnN0IHN3aXBlciA9IHRoaXM7XG4gIGNvbnN0IHtcbiAgICBwYXJhbXNcbiAgfSA9IHN3aXBlcjtcbiAgaWYgKHBhcmFtcy5jc3NNb2RlKSByZXR1cm47XG4gIGlmIChwYXJhbXMuYXV0b0hlaWdodCkge1xuICAgIHN3aXBlci51cGRhdGVBdXRvSGVpZ2h0KCk7XG4gIH1cbiAgdHJhbnNpdGlvbkVtaXQoe1xuICAgIHN3aXBlcixcbiAgICBydW5DYWxsYmFja3MsXG4gICAgZGlyZWN0aW9uLFxuICAgIHN0ZXA6ICdTdGFydCdcbiAgfSk7XG59XG5cbmZ1bmN0aW9uIHRyYW5zaXRpb25FbmQocnVuQ2FsbGJhY2tzLCBkaXJlY3Rpb24pIHtcbiAgaWYgKHJ1bkNhbGxiYWNrcyA9PT0gdm9pZCAwKSB7XG4gICAgcnVuQ2FsbGJhY2tzID0gdHJ1ZTtcbiAgfVxuICBjb25zdCBzd2lwZXIgPSB0aGlzO1xuICBjb25zdCB7XG4gICAgcGFyYW1zXG4gIH0gPSBzd2lwZXI7XG4gIHN3aXBlci5hbmltYXRpbmcgPSBmYWxzZTtcbiAgaWYgKHBhcmFtcy5jc3NNb2RlKSByZXR1cm47XG4gIHN3aXBlci5zZXRUcmFuc2l0aW9uKDApO1xuICB0cmFuc2l0aW9uRW1pdCh7XG4gICAgc3dpcGVyLFxuICAgIHJ1bkNhbGxiYWNrcyxcbiAgICBkaXJlY3Rpb24sXG4gICAgc3RlcDogJ0VuZCdcbiAgfSk7XG59XG5cbnZhciB0cmFuc2l0aW9uID0ge1xuICBzZXRUcmFuc2l0aW9uLFxuICB0cmFuc2l0aW9uU3RhcnQsXG4gIHRyYW5zaXRpb25FbmRcbn07XG5cbmZ1bmN0aW9uIHNsaWRlVG8oaW5kZXgsIHNwZWVkLCBydW5DYWxsYmFja3MsIGludGVybmFsLCBpbml0aWFsKSB7XG4gIGlmIChpbmRleCA9PT0gdm9pZCAwKSB7XG4gICAgaW5kZXggPSAwO1xuICB9XG4gIGlmIChydW5DYWxsYmFja3MgPT09IHZvaWQgMCkge1xuICAgIHJ1bkNhbGxiYWNrcyA9IHRydWU7XG4gIH1cbiAgaWYgKHR5cGVvZiBpbmRleCA9PT0gJ3N0cmluZycpIHtcbiAgICBpbmRleCA9IHBhcnNlSW50KGluZGV4LCAxMCk7XG4gIH1cbiAgY29uc3Qgc3dpcGVyID0gdGhpcztcbiAgbGV0IHNsaWRlSW5kZXggPSBpbmRleDtcbiAgaWYgKHNsaWRlSW5kZXggPCAwKSBzbGlkZUluZGV4ID0gMDtcbiAgY29uc3Qge1xuICAgIHBhcmFtcyxcbiAgICBzbmFwR3JpZCxcbiAgICBzbGlkZXNHcmlkLFxuICAgIHByZXZpb3VzSW5kZXgsXG4gICAgYWN0aXZlSW5kZXgsXG4gICAgcnRsVHJhbnNsYXRlOiBydGwsXG4gICAgd3JhcHBlckVsLFxuICAgIGVuYWJsZWRcbiAgfSA9IHN3aXBlcjtcbiAgaWYgKCFlbmFibGVkICYmICFpbnRlcm5hbCAmJiAhaW5pdGlhbCB8fCBzd2lwZXIuZGVzdHJveWVkIHx8IHN3aXBlci5hbmltYXRpbmcgJiYgcGFyYW1zLnByZXZlbnRJbnRlcmFjdGlvbk9uVHJhbnNpdGlvbikge1xuICAgIHJldHVybiBmYWxzZTtcbiAgfVxuICBpZiAodHlwZW9mIHNwZWVkID09PSAndW5kZWZpbmVkJykge1xuICAgIHNwZWVkID0gc3dpcGVyLnBhcmFtcy5zcGVlZDtcbiAgfVxuICBjb25zdCBza2lwID0gTWF0aC5taW4oc3dpcGVyLnBhcmFtcy5zbGlkZXNQZXJHcm91cFNraXAsIHNsaWRlSW5kZXgpO1xuICBsZXQgc25hcEluZGV4ID0gc2tpcCArIE1hdGguZmxvb3IoKHNsaWRlSW5kZXggLSBza2lwKSAvIHN3aXBlci5wYXJhbXMuc2xpZGVzUGVyR3JvdXApO1xuICBpZiAoc25hcEluZGV4ID49IHNuYXBHcmlkLmxlbmd0aCkgc25hcEluZGV4ID0gc25hcEdyaWQubGVuZ3RoIC0gMTtcbiAgY29uc3QgdHJhbnNsYXRlID0gLXNuYXBHcmlkW3NuYXBJbmRleF07XG4gIC8vIE5vcm1hbGl6ZSBzbGlkZUluZGV4XG4gIGlmIChwYXJhbXMubm9ybWFsaXplU2xpZGVJbmRleCkge1xuICAgIGZvciAobGV0IGkgPSAwOyBpIDwgc2xpZGVzR3JpZC5sZW5ndGg7IGkgKz0gMSkge1xuICAgICAgY29uc3Qgbm9ybWFsaXplZFRyYW5zbGF0ZSA9IC1NYXRoLmZsb29yKHRyYW5zbGF0ZSAqIDEwMCk7XG4gICAgICBjb25zdCBub3JtYWxpemVkR3JpZCA9IE1hdGguZmxvb3Ioc2xpZGVzR3JpZFtpXSAqIDEwMCk7XG4gICAgICBjb25zdCBub3JtYWxpemVkR3JpZE5leHQgPSBNYXRoLmZsb29yKHNsaWRlc0dyaWRbaSArIDFdICogMTAwKTtcbiAgICAgIGlmICh0eXBlb2Ygc2xpZGVzR3JpZFtpICsgMV0gIT09ICd1bmRlZmluZWQnKSB7XG4gICAgICAgIGlmIChub3JtYWxpemVkVHJhbnNsYXRlID49IG5vcm1hbGl6ZWRHcmlkICYmIG5vcm1hbGl6ZWRUcmFuc2xhdGUgPCBub3JtYWxpemVkR3JpZE5leHQgLSAobm9ybWFsaXplZEdyaWROZXh0IC0gbm9ybWFsaXplZEdyaWQpIC8gMikge1xuICAgICAgICAgIHNsaWRlSW5kZXggPSBpO1xuICAgICAgICB9IGVsc2UgaWYgKG5vcm1hbGl6ZWRUcmFuc2xhdGUgPj0gbm9ybWFsaXplZEdyaWQgJiYgbm9ybWFsaXplZFRyYW5zbGF0ZSA8IG5vcm1hbGl6ZWRHcmlkTmV4dCkge1xuICAgICAgICAgIHNsaWRlSW5kZXggPSBpICsgMTtcbiAgICAgICAgfVxuICAgICAgfSBlbHNlIGlmIChub3JtYWxpemVkVHJhbnNsYXRlID49IG5vcm1hbGl6ZWRHcmlkKSB7XG4gICAgICAgIHNsaWRlSW5kZXggPSBpO1xuICAgICAgfVxuICAgIH1cbiAgfVxuICAvLyBEaXJlY3Rpb25zIGxvY2tzXG4gIGlmIChzd2lwZXIuaW5pdGlhbGl6ZWQgJiYgc2xpZGVJbmRleCAhPT0gYWN0aXZlSW5kZXgpIHtcbiAgICBpZiAoIXN3aXBlci5hbGxvd1NsaWRlTmV4dCAmJiAocnRsID8gdHJhbnNsYXRlID4gc3dpcGVyLnRyYW5zbGF0ZSAmJiB0cmFuc2xhdGUgPiBzd2lwZXIubWluVHJhbnNsYXRlKCkgOiB0cmFuc2xhdGUgPCBzd2lwZXIudHJhbnNsYXRlICYmIHRyYW5zbGF0ZSA8IHN3aXBlci5taW5UcmFuc2xhdGUoKSkpIHtcbiAgICAgIHJldHVybiBmYWxzZTtcbiAgICB9XG4gICAgaWYgKCFzd2lwZXIuYWxsb3dTbGlkZVByZXYgJiYgdHJhbnNsYXRlID4gc3dpcGVyLnRyYW5zbGF0ZSAmJiB0cmFuc2xhdGUgPiBzd2lwZXIubWF4VHJhbnNsYXRlKCkpIHtcbiAgICAgIGlmICgoYWN0aXZlSW5kZXggfHwgMCkgIT09IHNsaWRlSW5kZXgpIHtcbiAgICAgICAgcmV0dXJuIGZhbHNlO1xuICAgICAgfVxuICAgIH1cbiAgfVxuICBpZiAoc2xpZGVJbmRleCAhPT0gKHByZXZpb3VzSW5kZXggfHwgMCkgJiYgcnVuQ2FsbGJhY2tzKSB7XG4gICAgc3dpcGVyLmVtaXQoJ2JlZm9yZVNsaWRlQ2hhbmdlU3RhcnQnKTtcbiAgfVxuXG4gIC8vIFVwZGF0ZSBwcm9ncmVzc1xuICBzd2lwZXIudXBkYXRlUHJvZ3Jlc3ModHJhbnNsYXRlKTtcbiAgbGV0IGRpcmVjdGlvbjtcbiAgaWYgKHNsaWRlSW5kZXggPiBhY3RpdmVJbmRleCkgZGlyZWN0aW9uID0gJ25leHQnO2Vsc2UgaWYgKHNsaWRlSW5kZXggPCBhY3RpdmVJbmRleCkgZGlyZWN0aW9uID0gJ3ByZXYnO2Vsc2UgZGlyZWN0aW9uID0gJ3Jlc2V0JztcblxuICAvLyBpbml0aWFsIHZpcnR1YWxcbiAgY29uc3QgaXNWaXJ0dWFsID0gc3dpcGVyLnZpcnR1YWwgJiYgc3dpcGVyLnBhcmFtcy52aXJ0dWFsLmVuYWJsZWQ7XG4gIGNvbnN0IGlzSW5pdGlhbFZpcnR1YWwgPSBpc1ZpcnR1YWwgJiYgaW5pdGlhbDtcbiAgLy8gVXBkYXRlIEluZGV4XG4gIGlmICghaXNJbml0aWFsVmlydHVhbCAmJiAocnRsICYmIC10cmFuc2xhdGUgPT09IHN3aXBlci50cmFuc2xhdGUgfHwgIXJ0bCAmJiB0cmFuc2xhdGUgPT09IHN3aXBlci50cmFuc2xhdGUpKSB7XG4gICAgc3dpcGVyLnVwZGF0ZUFjdGl2ZUluZGV4KHNsaWRlSW5kZXgpO1xuICAgIC8vIFVwZGF0ZSBIZWlnaHRcbiAgICBpZiAocGFyYW1zLmF1dG9IZWlnaHQpIHtcbiAgICAgIHN3aXBlci51cGRhdGVBdXRvSGVpZ2h0KCk7XG4gICAgfVxuICAgIHN3aXBlci51cGRhdGVTbGlkZXNDbGFzc2VzKCk7XG4gICAgaWYgKHBhcmFtcy5lZmZlY3QgIT09ICdzbGlkZScpIHtcbiAgICAgIHN3aXBlci5zZXRUcmFuc2xhdGUodHJhbnNsYXRlKTtcbiAgICB9XG4gICAgaWYgKGRpcmVjdGlvbiAhPT0gJ3Jlc2V0Jykge1xuICAgICAgc3dpcGVyLnRyYW5zaXRpb25TdGFydChydW5DYWxsYmFja3MsIGRpcmVjdGlvbik7XG4gICAgICBzd2lwZXIudHJhbnNpdGlvbkVuZChydW5DYWxsYmFja3MsIGRpcmVjdGlvbik7XG4gICAgfVxuICAgIHJldHVybiBmYWxzZTtcbiAgfVxuICBpZiAocGFyYW1zLmNzc01vZGUpIHtcbiAgICBjb25zdCBpc0ggPSBzd2lwZXIuaXNIb3Jpem9udGFsKCk7XG4gICAgY29uc3QgdCA9IHJ0bCA/IHRyYW5zbGF0ZSA6IC10cmFuc2xhdGU7XG4gICAgaWYgKHNwZWVkID09PSAwKSB7XG4gICAgICBpZiAoaXNWaXJ0dWFsKSB7XG4gICAgICAgIHN3aXBlci53cmFwcGVyRWwuc3R5bGUuc2Nyb2xsU25hcFR5cGUgPSAnbm9uZSc7XG4gICAgICAgIHN3aXBlci5faW1tZWRpYXRlVmlydHVhbCA9IHRydWU7XG4gICAgICB9XG4gICAgICBpZiAoaXNWaXJ0dWFsICYmICFzd2lwZXIuX2Nzc01vZGVWaXJ0dWFsSW5pdGlhbFNldCAmJiBzd2lwZXIucGFyYW1zLmluaXRpYWxTbGlkZSA+IDApIHtcbiAgICAgICAgc3dpcGVyLl9jc3NNb2RlVmlydHVhbEluaXRpYWxTZXQgPSB0cnVlO1xuICAgICAgICByZXF1ZXN0QW5pbWF0aW9uRnJhbWUoKCkgPT4ge1xuICAgICAgICAgIHdyYXBwZXJFbFtpc0ggPyAnc2Nyb2xsTGVmdCcgOiAnc2Nyb2xsVG9wJ10gPSB0O1xuICAgICAgICB9KTtcbiAgICAgIH0gZWxzZSB7XG4gICAgICAgIHdyYXBwZXJFbFtpc0ggPyAnc2Nyb2xsTGVmdCcgOiAnc2Nyb2xsVG9wJ10gPSB0O1xuICAgICAgfVxuICAgICAgaWYgKGlzVmlydHVhbCkge1xuICAgICAgICByZXF1ZXN0QW5pbWF0aW9uRnJhbWUoKCkgPT4ge1xuICAgICAgICAgIHN3aXBlci53cmFwcGVyRWwuc3R5bGUuc2Nyb2xsU25hcFR5cGUgPSAnJztcbiAgICAgICAgICBzd2lwZXIuX2ltbWVkaWF0ZVZpcnR1YWwgPSBmYWxzZTtcbiAgICAgICAgfSk7XG4gICAgICB9XG4gICAgfSBlbHNlIHtcbiAgICAgIGlmICghc3dpcGVyLnN1cHBvcnQuc21vb3RoU2Nyb2xsKSB7XG4gICAgICAgIGFuaW1hdGVDU1NNb2RlU2Nyb2xsKHtcbiAgICAgICAgICBzd2lwZXIsXG4gICAgICAgICAgdGFyZ2V0UG9zaXRpb246IHQsXG4gICAgICAgICAgc2lkZTogaXNIID8gJ2xlZnQnIDogJ3RvcCdcbiAgICAgICAgfSk7XG4gICAgICAgIHJldHVybiB0cnVlO1xuICAgICAgfVxuICAgICAgd3JhcHBlckVsLnNjcm9sbFRvKHtcbiAgICAgICAgW2lzSCA/ICdsZWZ0JyA6ICd0b3AnXTogdCxcbiAgICAgICAgYmVoYXZpb3I6ICdzbW9vdGgnXG4gICAgICB9KTtcbiAgICB9XG4gICAgcmV0dXJuIHRydWU7XG4gIH1cbiAgc3dpcGVyLnNldFRyYW5zaXRpb24oc3BlZWQpO1xuICBzd2lwZXIuc2V0VHJhbnNsYXRlKHRyYW5zbGF0ZSk7XG4gIHN3aXBlci51cGRhdGVBY3RpdmVJbmRleChzbGlkZUluZGV4KTtcbiAgc3dpcGVyLnVwZGF0ZVNsaWRlc0NsYXNzZXMoKTtcbiAgc3dpcGVyLmVtaXQoJ2JlZm9yZVRyYW5zaXRpb25TdGFydCcsIHNwZWVkLCBpbnRlcm5hbCk7XG4gIHN3aXBlci50cmFuc2l0aW9uU3RhcnQocnVuQ2FsbGJhY2tzLCBkaXJlY3Rpb24pO1xuICBpZiAoc3BlZWQgPT09IDApIHtcbiAgICBzd2lwZXIudHJhbnNpdGlvbkVuZChydW5DYWxsYmFja3MsIGRpcmVjdGlvbik7XG4gIH0gZWxzZSBpZiAoIXN3aXBlci5hbmltYXRpbmcpIHtcbiAgICBzd2lwZXIuYW5pbWF0aW5nID0gdHJ1ZTtcbiAgICBpZiAoIXN3aXBlci5vblNsaWRlVG9XcmFwcGVyVHJhbnNpdGlvbkVuZCkge1xuICAgICAgc3dpcGVyLm9uU2xpZGVUb1dyYXBwZXJUcmFuc2l0aW9uRW5kID0gZnVuY3Rpb24gdHJhbnNpdGlvbkVuZChlKSB7XG4gICAgICAgIGlmICghc3dpcGVyIHx8IHN3aXBlci5kZXN0cm95ZWQpIHJldHVybjtcbiAgICAgICAgaWYgKGUudGFyZ2V0ICE9PSB0aGlzKSByZXR1cm47XG4gICAgICAgIHN3aXBlci53cmFwcGVyRWwucmVtb3ZlRXZlbnRMaXN0ZW5lcigndHJhbnNpdGlvbmVuZCcsIHN3aXBlci5vblNsaWRlVG9XcmFwcGVyVHJhbnNpdGlvbkVuZCk7XG4gICAgICAgIHN3aXBlci5vblNsaWRlVG9XcmFwcGVyVHJhbnNpdGlvbkVuZCA9IG51bGw7XG4gICAgICAgIGRlbGV0ZSBzd2lwZXIub25TbGlkZVRvV3JhcHBlclRyYW5zaXRpb25FbmQ7XG4gICAgICAgIHN3aXBlci50cmFuc2l0aW9uRW5kKHJ1bkNhbGxiYWNrcywgZGlyZWN0aW9uKTtcbiAgICAgIH07XG4gICAgfVxuICAgIHN3aXBlci53cmFwcGVyRWwuYWRkRXZlbnRMaXN0ZW5lcigndHJhbnNpdGlvbmVuZCcsIHN3aXBlci5vblNsaWRlVG9XcmFwcGVyVHJhbnNpdGlvbkVuZCk7XG4gIH1cbiAgcmV0dXJuIHRydWU7XG59XG5cbmZ1bmN0aW9uIHNsaWRlVG9Mb29wKGluZGV4LCBzcGVlZCwgcnVuQ2FsbGJhY2tzLCBpbnRlcm5hbCkge1xuICBpZiAoaW5kZXggPT09IHZvaWQgMCkge1xuICAgIGluZGV4ID0gMDtcbiAgfVxuICBpZiAocnVuQ2FsbGJhY2tzID09PSB2b2lkIDApIHtcbiAgICBydW5DYWxsYmFja3MgPSB0cnVlO1xuICB9XG4gIGlmICh0eXBlb2YgaW5kZXggPT09ICdzdHJpbmcnKSB7XG4gICAgY29uc3QgaW5kZXhBc051bWJlciA9IHBhcnNlSW50KGluZGV4LCAxMCk7XG4gICAgaW5kZXggPSBpbmRleEFzTnVtYmVyO1xuICB9XG4gIGNvbnN0IHN3aXBlciA9IHRoaXM7XG4gIGlmIChzd2lwZXIuZGVzdHJveWVkKSByZXR1cm47XG4gIGlmICh0eXBlb2Ygc3BlZWQgPT09ICd1bmRlZmluZWQnKSB7XG4gICAgc3BlZWQgPSBzd2lwZXIucGFyYW1zLnNwZWVkO1xuICB9XG4gIGNvbnN0IGdyaWRFbmFibGVkID0gc3dpcGVyLmdyaWQgJiYgc3dpcGVyLnBhcmFtcy5ncmlkICYmIHN3aXBlci5wYXJhbXMuZ3JpZC5yb3dzID4gMTtcbiAgbGV0IG5ld0luZGV4ID0gaW5kZXg7XG4gIGlmIChzd2lwZXIucGFyYW1zLmxvb3ApIHtcbiAgICBpZiAoc3dpcGVyLnZpcnR1YWwgJiYgc3dpcGVyLnBhcmFtcy52aXJ0dWFsLmVuYWJsZWQpIHtcbiAgICAgIC8vIGVzbGludC1kaXNhYmxlLW5leHQtbGluZVxuICAgICAgbmV3SW5kZXggPSBuZXdJbmRleCArIHN3aXBlci52aXJ0dWFsLnNsaWRlc0JlZm9yZTtcbiAgICB9IGVsc2Uge1xuICAgICAgbGV0IHRhcmdldFNsaWRlSW5kZXg7XG4gICAgICBpZiAoZ3JpZEVuYWJsZWQpIHtcbiAgICAgICAgY29uc3Qgc2xpZGVJbmRleCA9IG5ld0luZGV4ICogc3dpcGVyLnBhcmFtcy5ncmlkLnJvd3M7XG4gICAgICAgIHRhcmdldFNsaWRlSW5kZXggPSBzd2lwZXIuc2xpZGVzLmZpbHRlcihzbGlkZUVsID0+IHNsaWRlRWwuZ2V0QXR0cmlidXRlKCdkYXRhLXN3aXBlci1zbGlkZS1pbmRleCcpICogMSA9PT0gc2xpZGVJbmRleClbMF0uY29sdW1uO1xuICAgICAgfSBlbHNlIHtcbiAgICAgICAgdGFyZ2V0U2xpZGVJbmRleCA9IHN3aXBlci5nZXRTbGlkZUluZGV4QnlEYXRhKG5ld0luZGV4KTtcbiAgICAgIH1cbiAgICAgIGNvbnN0IGNvbHMgPSBncmlkRW5hYmxlZCA/IE1hdGguY2VpbChzd2lwZXIuc2xpZGVzLmxlbmd0aCAvIHN3aXBlci5wYXJhbXMuZ3JpZC5yb3dzKSA6IHN3aXBlci5zbGlkZXMubGVuZ3RoO1xuICAgICAgY29uc3Qge1xuICAgICAgICBjZW50ZXJlZFNsaWRlc1xuICAgICAgfSA9IHN3aXBlci5wYXJhbXM7XG4gICAgICBsZXQgc2xpZGVzUGVyVmlldyA9IHN3aXBlci5wYXJhbXMuc2xpZGVzUGVyVmlldztcbiAgICAgIGlmIChzbGlkZXNQZXJWaWV3ID09PSAnYXV0bycpIHtcbiAgICAgICAgc2xpZGVzUGVyVmlldyA9IHN3aXBlci5zbGlkZXNQZXJWaWV3RHluYW1pYygpO1xuICAgICAgfSBlbHNlIHtcbiAgICAgICAgc2xpZGVzUGVyVmlldyA9IE1hdGguY2VpbChwYXJzZUZsb2F0KHN3aXBlci5wYXJhbXMuc2xpZGVzUGVyVmlldywgMTApKTtcbiAgICAgICAgaWYgKGNlbnRlcmVkU2xpZGVzICYmIHNsaWRlc1BlclZpZXcgJSAyID09PSAwKSB7XG4gICAgICAgICAgc2xpZGVzUGVyVmlldyA9IHNsaWRlc1BlclZpZXcgKyAxO1xuICAgICAgICB9XG4gICAgICB9XG4gICAgICBsZXQgbmVlZExvb3BGaXggPSBjb2xzIC0gdGFyZ2V0U2xpZGVJbmRleCA8IHNsaWRlc1BlclZpZXc7XG4gICAgICBpZiAoY2VudGVyZWRTbGlkZXMpIHtcbiAgICAgICAgbmVlZExvb3BGaXggPSBuZWVkTG9vcEZpeCB8fCB0YXJnZXRTbGlkZUluZGV4IDwgTWF0aC5jZWlsKHNsaWRlc1BlclZpZXcgLyAyKTtcbiAgICAgIH1cbiAgICAgIGlmIChpbnRlcm5hbCAmJiBjZW50ZXJlZFNsaWRlcyAmJiBzd2lwZXIucGFyYW1zLnNsaWRlc1BlclZpZXcgIT09ICdhdXRvJyAmJiAhZ3JpZEVuYWJsZWQpIHtcbiAgICAgICAgbmVlZExvb3BGaXggPSBmYWxzZTtcbiAgICAgIH1cbiAgICAgIGlmIChuZWVkTG9vcEZpeCkge1xuICAgICAgICBjb25zdCBkaXJlY3Rpb24gPSBjZW50ZXJlZFNsaWRlcyA/IHRhcmdldFNsaWRlSW5kZXggPCBzd2lwZXIuYWN0aXZlSW5kZXggPyAncHJldicgOiAnbmV4dCcgOiB0YXJnZXRTbGlkZUluZGV4IC0gc3dpcGVyLmFjdGl2ZUluZGV4IC0gMSA8IHN3aXBlci5wYXJhbXMuc2xpZGVzUGVyVmlldyA/ICduZXh0JyA6ICdwcmV2JztcbiAgICAgICAgc3dpcGVyLmxvb3BGaXgoe1xuICAgICAgICAgIGRpcmVjdGlvbixcbiAgICAgICAgICBzbGlkZVRvOiB0cnVlLFxuICAgICAgICAgIGFjdGl2ZVNsaWRlSW5kZXg6IGRpcmVjdGlvbiA9PT0gJ25leHQnID8gdGFyZ2V0U2xpZGVJbmRleCArIDEgOiB0YXJnZXRTbGlkZUluZGV4IC0gY29scyArIDEsXG4gICAgICAgICAgc2xpZGVSZWFsSW5kZXg6IGRpcmVjdGlvbiA9PT0gJ25leHQnID8gc3dpcGVyLnJlYWxJbmRleCA6IHVuZGVmaW5lZFxuICAgICAgICB9KTtcbiAgICAgIH1cbiAgICAgIGlmIChncmlkRW5hYmxlZCkge1xuICAgICAgICBjb25zdCBzbGlkZUluZGV4ID0gbmV3SW5kZXggKiBzd2lwZXIucGFyYW1zLmdyaWQucm93cztcbiAgICAgICAgbmV3SW5kZXggPSBzd2lwZXIuc2xpZGVzLmZpbHRlcihzbGlkZUVsID0+IHNsaWRlRWwuZ2V0QXR0cmlidXRlKCdkYXRhLXN3aXBlci1zbGlkZS1pbmRleCcpICogMSA9PT0gc2xpZGVJbmRleClbMF0uY29sdW1uO1xuICAgICAgfSBlbHNlIHtcbiAgICAgICAgbmV3SW5kZXggPSBzd2lwZXIuZ2V0U2xpZGVJbmRleEJ5RGF0YShuZXdJbmRleCk7XG4gICAgICB9XG4gICAgfVxuICB9XG4gIHJlcXVlc3RBbmltYXRpb25GcmFtZSgoKSA9PiB7XG4gICAgc3dpcGVyLnNsaWRlVG8obmV3SW5kZXgsIHNwZWVkLCBydW5DYWxsYmFja3MsIGludGVybmFsKTtcbiAgfSk7XG4gIHJldHVybiBzd2lwZXI7XG59XG5cbi8qIGVzbGludCBuby11bnVzZWQtdmFyczogXCJvZmZcIiAqL1xuZnVuY3Rpb24gc2xpZGVOZXh0KHNwZWVkLCBydW5DYWxsYmFja3MsIGludGVybmFsKSB7XG4gIGlmIChydW5DYWxsYmFja3MgPT09IHZvaWQgMCkge1xuICAgIHJ1bkNhbGxiYWNrcyA9IHRydWU7XG4gIH1cbiAgY29uc3Qgc3dpcGVyID0gdGhpcztcbiAgY29uc3Qge1xuICAgIGVuYWJsZWQsXG4gICAgcGFyYW1zLFxuICAgIGFuaW1hdGluZ1xuICB9ID0gc3dpcGVyO1xuICBpZiAoIWVuYWJsZWQgfHwgc3dpcGVyLmRlc3Ryb3llZCkgcmV0dXJuIHN3aXBlcjtcbiAgaWYgKHR5cGVvZiBzcGVlZCA9PT0gJ3VuZGVmaW5lZCcpIHtcbiAgICBzcGVlZCA9IHN3aXBlci5wYXJhbXMuc3BlZWQ7XG4gIH1cbiAgbGV0IHBlckdyb3VwID0gcGFyYW1zLnNsaWRlc1Blckdyb3VwO1xuICBpZiAocGFyYW1zLnNsaWRlc1BlclZpZXcgPT09ICdhdXRvJyAmJiBwYXJhbXMuc2xpZGVzUGVyR3JvdXAgPT09IDEgJiYgcGFyYW1zLnNsaWRlc1Blckdyb3VwQXV0bykge1xuICAgIHBlckdyb3VwID0gTWF0aC5tYXgoc3dpcGVyLnNsaWRlc1BlclZpZXdEeW5hbWljKCdjdXJyZW50JywgdHJ1ZSksIDEpO1xuICB9XG4gIGNvbnN0IGluY3JlbWVudCA9IHN3aXBlci5hY3RpdmVJbmRleCA8IHBhcmFtcy5zbGlkZXNQZXJHcm91cFNraXAgPyAxIDogcGVyR3JvdXA7XG4gIGNvbnN0IGlzVmlydHVhbCA9IHN3aXBlci52aXJ0dWFsICYmIHBhcmFtcy52aXJ0dWFsLmVuYWJsZWQ7XG4gIGlmIChwYXJhbXMubG9vcCkge1xuICAgIGlmIChhbmltYXRpbmcgJiYgIWlzVmlydHVhbCAmJiBwYXJhbXMubG9vcFByZXZlbnRzU2xpZGluZykgcmV0dXJuIGZhbHNlO1xuICAgIHN3aXBlci5sb29wRml4KHtcbiAgICAgIGRpcmVjdGlvbjogJ25leHQnXG4gICAgfSk7XG4gICAgLy8gZXNsaW50LWRpc2FibGUtbmV4dC1saW5lXG4gICAgc3dpcGVyLl9jbGllbnRMZWZ0ID0gc3dpcGVyLndyYXBwZXJFbC5jbGllbnRMZWZ0O1xuICAgIGlmIChzd2lwZXIuYWN0aXZlSW5kZXggPT09IHN3aXBlci5zbGlkZXMubGVuZ3RoIC0gMSAmJiBwYXJhbXMuY3NzTW9kZSkge1xuICAgICAgcmVxdWVzdEFuaW1hdGlvbkZyYW1lKCgpID0+IHtcbiAgICAgICAgc3dpcGVyLnNsaWRlVG8oc3dpcGVyLmFjdGl2ZUluZGV4ICsgaW5jcmVtZW50LCBzcGVlZCwgcnVuQ2FsbGJhY2tzLCBpbnRlcm5hbCk7XG4gICAgICB9KTtcbiAgICAgIHJldHVybiB0cnVlO1xuICAgIH1cbiAgfVxuICBpZiAocGFyYW1zLnJld2luZCAmJiBzd2lwZXIuaXNFbmQpIHtcbiAgICByZXR1cm4gc3dpcGVyLnNsaWRlVG8oMCwgc3BlZWQsIHJ1bkNhbGxiYWNrcywgaW50ZXJuYWwpO1xuICB9XG4gIHJldHVybiBzd2lwZXIuc2xpZGVUbyhzd2lwZXIuYWN0aXZlSW5kZXggKyBpbmNyZW1lbnQsIHNwZWVkLCBydW5DYWxsYmFja3MsIGludGVybmFsKTtcbn1cblxuLyogZXNsaW50IG5vLXVudXNlZC12YXJzOiBcIm9mZlwiICovXG5mdW5jdGlvbiBzbGlkZVByZXYoc3BlZWQsIHJ1bkNhbGxiYWNrcywgaW50ZXJuYWwpIHtcbiAgaWYgKHJ1bkNhbGxiYWNrcyA9PT0gdm9pZCAwKSB7XG4gICAgcnVuQ2FsbGJhY2tzID0gdHJ1ZTtcbiAgfVxuICBjb25zdCBzd2lwZXIgPSB0aGlzO1xuICBjb25zdCB7XG4gICAgcGFyYW1zLFxuICAgIHNuYXBHcmlkLFxuICAgIHNsaWRlc0dyaWQsXG4gICAgcnRsVHJhbnNsYXRlLFxuICAgIGVuYWJsZWQsXG4gICAgYW5pbWF0aW5nXG4gIH0gPSBzd2lwZXI7XG4gIGlmICghZW5hYmxlZCB8fCBzd2lwZXIuZGVzdHJveWVkKSByZXR1cm4gc3dpcGVyO1xuICBpZiAodHlwZW9mIHNwZWVkID09PSAndW5kZWZpbmVkJykge1xuICAgIHNwZWVkID0gc3dpcGVyLnBhcmFtcy5zcGVlZDtcbiAgfVxuICBjb25zdCBpc1ZpcnR1YWwgPSBzd2lwZXIudmlydHVhbCAmJiBwYXJhbXMudmlydHVhbC5lbmFibGVkO1xuICBpZiAocGFyYW1zLmxvb3ApIHtcbiAgICBpZiAoYW5pbWF0aW5nICYmICFpc1ZpcnR1YWwgJiYgcGFyYW1zLmxvb3BQcmV2ZW50c1NsaWRpbmcpIHJldHVybiBmYWxzZTtcbiAgICBzd2lwZXIubG9vcEZpeCh7XG4gICAgICBkaXJlY3Rpb246ICdwcmV2J1xuICAgIH0pO1xuICAgIC8vIGVzbGludC1kaXNhYmxlLW5leHQtbGluZVxuICAgIHN3aXBlci5fY2xpZW50TGVmdCA9IHN3aXBlci53cmFwcGVyRWwuY2xpZW50TGVmdDtcbiAgfVxuICBjb25zdCB0cmFuc2xhdGUgPSBydGxUcmFuc2xhdGUgPyBzd2lwZXIudHJhbnNsYXRlIDogLXN3aXBlci50cmFuc2xhdGU7XG4gIGZ1bmN0aW9uIG5vcm1hbGl6ZSh2YWwpIHtcbiAgICBpZiAodmFsIDwgMCkgcmV0dXJuIC1NYXRoLmZsb29yKE1hdGguYWJzKHZhbCkpO1xuICAgIHJldHVybiBNYXRoLmZsb29yKHZhbCk7XG4gIH1cbiAgY29uc3Qgbm9ybWFsaXplZFRyYW5zbGF0ZSA9IG5vcm1hbGl6ZSh0cmFuc2xhdGUpO1xuICBjb25zdCBub3JtYWxpemVkU25hcEdyaWQgPSBzbmFwR3JpZC5tYXAodmFsID0+IG5vcm1hbGl6ZSh2YWwpKTtcbiAgbGV0IHByZXZTbmFwID0gc25hcEdyaWRbbm9ybWFsaXplZFNuYXBHcmlkLmluZGV4T2Yobm9ybWFsaXplZFRyYW5zbGF0ZSkgLSAxXTtcbiAgaWYgKHR5cGVvZiBwcmV2U25hcCA9PT0gJ3VuZGVmaW5lZCcgJiYgcGFyYW1zLmNzc01vZGUpIHtcbiAgICBsZXQgcHJldlNuYXBJbmRleDtcbiAgICBzbmFwR3JpZC5mb3JFYWNoKChzbmFwLCBzbmFwSW5kZXgpID0+IHtcbiAgICAgIGlmIChub3JtYWxpemVkVHJhbnNsYXRlID49IHNuYXApIHtcbiAgICAgICAgLy8gcHJldlNuYXAgPSBzbmFwO1xuICAgICAgICBwcmV2U25hcEluZGV4ID0gc25hcEluZGV4O1xuICAgICAgfVxuICAgIH0pO1xuICAgIGlmICh0eXBlb2YgcHJldlNuYXBJbmRleCAhPT0gJ3VuZGVmaW5lZCcpIHtcbiAgICAgIHByZXZTbmFwID0gc25hcEdyaWRbcHJldlNuYXBJbmRleCA+IDAgPyBwcmV2U25hcEluZGV4IC0gMSA6IHByZXZTbmFwSW5kZXhdO1xuICAgIH1cbiAgfVxuICBsZXQgcHJldkluZGV4ID0gMDtcbiAgaWYgKHR5cGVvZiBwcmV2U25hcCAhPT0gJ3VuZGVmaW5lZCcpIHtcbiAgICBwcmV2SW5kZXggPSBzbGlkZXNHcmlkLmluZGV4T2YocHJldlNuYXApO1xuICAgIGlmIChwcmV2SW5kZXggPCAwKSBwcmV2SW5kZXggPSBzd2lwZXIuYWN0aXZlSW5kZXggLSAxO1xuICAgIGlmIChwYXJhbXMuc2xpZGVzUGVyVmlldyA9PT0gJ2F1dG8nICYmIHBhcmFtcy5zbGlkZXNQZXJHcm91cCA9PT0gMSAmJiBwYXJhbXMuc2xpZGVzUGVyR3JvdXBBdXRvKSB7XG4gICAgICBwcmV2SW5kZXggPSBwcmV2SW5kZXggLSBzd2lwZXIuc2xpZGVzUGVyVmlld0R5bmFtaWMoJ3ByZXZpb3VzJywgdHJ1ZSkgKyAxO1xuICAgICAgcHJldkluZGV4ID0gTWF0aC5tYXgocHJldkluZGV4LCAwKTtcbiAgICB9XG4gIH1cbiAgaWYgKHBhcmFtcy5yZXdpbmQgJiYgc3dpcGVyLmlzQmVnaW5uaW5nKSB7XG4gICAgY29uc3QgbGFzdEluZGV4ID0gc3dpcGVyLnBhcmFtcy52aXJ0dWFsICYmIHN3aXBlci5wYXJhbXMudmlydHVhbC5lbmFibGVkICYmIHN3aXBlci52aXJ0dWFsID8gc3dpcGVyLnZpcnR1YWwuc2xpZGVzLmxlbmd0aCAtIDEgOiBzd2lwZXIuc2xpZGVzLmxlbmd0aCAtIDE7XG4gICAgcmV0dXJuIHN3aXBlci5zbGlkZVRvKGxhc3RJbmRleCwgc3BlZWQsIHJ1bkNhbGxiYWNrcywgaW50ZXJuYWwpO1xuICB9IGVsc2UgaWYgKHBhcmFtcy5sb29wICYmIHN3aXBlci5hY3RpdmVJbmRleCA9PT0gMCAmJiBwYXJhbXMuY3NzTW9kZSkge1xuICAgIHJlcXVlc3RBbmltYXRpb25GcmFtZSgoKSA9PiB7XG4gICAgICBzd2lwZXIuc2xpZGVUbyhwcmV2SW5kZXgsIHNwZWVkLCBydW5DYWxsYmFja3MsIGludGVybmFsKTtcbiAgICB9KTtcbiAgICByZXR1cm4gdHJ1ZTtcbiAgfVxuICByZXR1cm4gc3dpcGVyLnNsaWRlVG8ocHJldkluZGV4LCBzcGVlZCwgcnVuQ2FsbGJhY2tzLCBpbnRlcm5hbCk7XG59XG5cbi8qIGVzbGludCBuby11bnVzZWQtdmFyczogXCJvZmZcIiAqL1xuZnVuY3Rpb24gc2xpZGVSZXNldChzcGVlZCwgcnVuQ2FsbGJhY2tzLCBpbnRlcm5hbCkge1xuICBpZiAocnVuQ2FsbGJhY2tzID09PSB2b2lkIDApIHtcbiAgICBydW5DYWxsYmFja3MgPSB0cnVlO1xuICB9XG4gIGNvbnN0IHN3aXBlciA9IHRoaXM7XG4gIGlmIChzd2lwZXIuZGVzdHJveWVkKSByZXR1cm47XG4gIGlmICh0eXBlb2Ygc3BlZWQgPT09ICd1bmRlZmluZWQnKSB7XG4gICAgc3BlZWQgPSBzd2lwZXIucGFyYW1zLnNwZWVkO1xuICB9XG4gIHJldHVybiBzd2lwZXIuc2xpZGVUbyhzd2lwZXIuYWN0aXZlSW5kZXgsIHNwZWVkLCBydW5DYWxsYmFja3MsIGludGVybmFsKTtcbn1cblxuLyogZXNsaW50IG5vLXVudXNlZC12YXJzOiBcIm9mZlwiICovXG5mdW5jdGlvbiBzbGlkZVRvQ2xvc2VzdChzcGVlZCwgcnVuQ2FsbGJhY2tzLCBpbnRlcm5hbCwgdGhyZXNob2xkKSB7XG4gIGlmIChydW5DYWxsYmFja3MgPT09IHZvaWQgMCkge1xuICAgIHJ1bkNhbGxiYWNrcyA9IHRydWU7XG4gIH1cbiAgaWYgKHRocmVzaG9sZCA9PT0gdm9pZCAwKSB7XG4gICAgdGhyZXNob2xkID0gMC41O1xuICB9XG4gIGNvbnN0IHN3aXBlciA9IHRoaXM7XG4gIGlmIChzd2lwZXIuZGVzdHJveWVkKSByZXR1cm47XG4gIGlmICh0eXBlb2Ygc3BlZWQgPT09ICd1bmRlZmluZWQnKSB7XG4gICAgc3BlZWQgPSBzd2lwZXIucGFyYW1zLnNwZWVkO1xuICB9XG4gIGxldCBpbmRleCA9IHN3aXBlci5hY3RpdmVJbmRleDtcbiAgY29uc3Qgc2tpcCA9IE1hdGgubWluKHN3aXBlci5wYXJhbXMuc2xpZGVzUGVyR3JvdXBTa2lwLCBpbmRleCk7XG4gIGNvbnN0IHNuYXBJbmRleCA9IHNraXAgKyBNYXRoLmZsb29yKChpbmRleCAtIHNraXApIC8gc3dpcGVyLnBhcmFtcy5zbGlkZXNQZXJHcm91cCk7XG4gIGNvbnN0IHRyYW5zbGF0ZSA9IHN3aXBlci5ydGxUcmFuc2xhdGUgPyBzd2lwZXIudHJhbnNsYXRlIDogLXN3aXBlci50cmFuc2xhdGU7XG4gIGlmICh0cmFuc2xhdGUgPj0gc3dpcGVyLnNuYXBHcmlkW3NuYXBJbmRleF0pIHtcbiAgICAvLyBUaGUgY3VycmVudCB0cmFuc2xhdGUgaXMgb24gb3IgYWZ0ZXIgdGhlIGN1cnJlbnQgc25hcCBpbmRleCwgc28gdGhlIGNob2ljZVxuICAgIC8vIGlzIGJldHdlZW4gdGhlIGN1cnJlbnQgaW5kZXggYW5kIHRoZSBvbmUgYWZ0ZXIgaXQuXG4gICAgY29uc3QgY3VycmVudFNuYXAgPSBzd2lwZXIuc25hcEdyaWRbc25hcEluZGV4XTtcbiAgICBjb25zdCBuZXh0U25hcCA9IHN3aXBlci5zbmFwR3JpZFtzbmFwSW5kZXggKyAxXTtcbiAgICBpZiAodHJhbnNsYXRlIC0gY3VycmVudFNuYXAgPiAobmV4dFNuYXAgLSBjdXJyZW50U25hcCkgKiB0aHJlc2hvbGQpIHtcbiAgICAgIGluZGV4ICs9IHN3aXBlci5wYXJhbXMuc2xpZGVzUGVyR3JvdXA7XG4gICAgfVxuICB9IGVsc2Uge1xuICAgIC8vIFRoZSBjdXJyZW50IHRyYW5zbGF0ZSBpcyBiZWZvcmUgdGhlIGN1cnJlbnQgc25hcCBpbmRleCwgc28gdGhlIGNob2ljZVxuICAgIC8vIGlzIGJldHdlZW4gdGhlIGN1cnJlbnQgaW5kZXggYW5kIHRoZSBvbmUgYmVmb3JlIGl0LlxuICAgIGNvbnN0IHByZXZTbmFwID0gc3dpcGVyLnNuYXBHcmlkW3NuYXBJbmRleCAtIDFdO1xuICAgIGNvbnN0IGN1cnJlbnRTbmFwID0gc3dpcGVyLnNuYXBHcmlkW3NuYXBJbmRleF07XG4gICAgaWYgKHRyYW5zbGF0ZSAtIHByZXZTbmFwIDw9IChjdXJyZW50U25hcCAtIHByZXZTbmFwKSAqIHRocmVzaG9sZCkge1xuICAgICAgaW5kZXggLT0gc3dpcGVyLnBhcmFtcy5zbGlkZXNQZXJHcm91cDtcbiAgICB9XG4gIH1cbiAgaW5kZXggPSBNYXRoLm1heChpbmRleCwgMCk7XG4gIGluZGV4ID0gTWF0aC5taW4oaW5kZXgsIHN3aXBlci5zbGlkZXNHcmlkLmxlbmd0aCAtIDEpO1xuICByZXR1cm4gc3dpcGVyLnNsaWRlVG8oaW5kZXgsIHNwZWVkLCBydW5DYWxsYmFja3MsIGludGVybmFsKTtcbn1cblxuZnVuY3Rpb24gc2xpZGVUb0NsaWNrZWRTbGlkZSgpIHtcbiAgY29uc3Qgc3dpcGVyID0gdGhpcztcbiAgaWYgKHN3aXBlci5kZXN0cm95ZWQpIHJldHVybjtcbiAgY29uc3Qge1xuICAgIHBhcmFtcyxcbiAgICBzbGlkZXNFbFxuICB9ID0gc3dpcGVyO1xuICBjb25zdCBzbGlkZXNQZXJWaWV3ID0gcGFyYW1zLnNsaWRlc1BlclZpZXcgPT09ICdhdXRvJyA/IHN3aXBlci5zbGlkZXNQZXJWaWV3RHluYW1pYygpIDogcGFyYW1zLnNsaWRlc1BlclZpZXc7XG4gIGxldCBzbGlkZVRvSW5kZXggPSBzd2lwZXIuY2xpY2tlZEluZGV4O1xuICBsZXQgcmVhbEluZGV4O1xuICBjb25zdCBzbGlkZVNlbGVjdG9yID0gc3dpcGVyLmlzRWxlbWVudCA/IGBzd2lwZXItc2xpZGVgIDogYC4ke3BhcmFtcy5zbGlkZUNsYXNzfWA7XG4gIGlmIChwYXJhbXMubG9vcCkge1xuICAgIGlmIChzd2lwZXIuYW5pbWF0aW5nKSByZXR1cm47XG4gICAgcmVhbEluZGV4ID0gcGFyc2VJbnQoc3dpcGVyLmNsaWNrZWRTbGlkZS5nZXRBdHRyaWJ1dGUoJ2RhdGEtc3dpcGVyLXNsaWRlLWluZGV4JyksIDEwKTtcbiAgICBpZiAocGFyYW1zLmNlbnRlcmVkU2xpZGVzKSB7XG4gICAgICBpZiAoc2xpZGVUb0luZGV4IDwgc3dpcGVyLmxvb3BlZFNsaWRlcyAtIHNsaWRlc1BlclZpZXcgLyAyIHx8IHNsaWRlVG9JbmRleCA+IHN3aXBlci5zbGlkZXMubGVuZ3RoIC0gc3dpcGVyLmxvb3BlZFNsaWRlcyArIHNsaWRlc1BlclZpZXcgLyAyKSB7XG4gICAgICAgIHN3aXBlci5sb29wRml4KCk7XG4gICAgICAgIHNsaWRlVG9JbmRleCA9IHN3aXBlci5nZXRTbGlkZUluZGV4KGVsZW1lbnRDaGlsZHJlbihzbGlkZXNFbCwgYCR7c2xpZGVTZWxlY3Rvcn1bZGF0YS1zd2lwZXItc2xpZGUtaW5kZXg9XCIke3JlYWxJbmRleH1cIl1gKVswXSk7XG4gICAgICAgIG5leHRUaWNrKCgpID0+IHtcbiAgICAgICAgICBzd2lwZXIuc2xpZGVUbyhzbGlkZVRvSW5kZXgpO1xuICAgICAgICB9KTtcbiAgICAgIH0gZWxzZSB7XG4gICAgICAgIHN3aXBlci5zbGlkZVRvKHNsaWRlVG9JbmRleCk7XG4gICAgICB9XG4gICAgfSBlbHNlIGlmIChzbGlkZVRvSW5kZXggPiBzd2lwZXIuc2xpZGVzLmxlbmd0aCAtIHNsaWRlc1BlclZpZXcpIHtcbiAgICAgIHN3aXBlci5sb29wRml4KCk7XG4gICAgICBzbGlkZVRvSW5kZXggPSBzd2lwZXIuZ2V0U2xpZGVJbmRleChlbGVtZW50Q2hpbGRyZW4oc2xpZGVzRWwsIGAke3NsaWRlU2VsZWN0b3J9W2RhdGEtc3dpcGVyLXNsaWRlLWluZGV4PVwiJHtyZWFsSW5kZXh9XCJdYClbMF0pO1xuICAgICAgbmV4dFRpY2soKCkgPT4ge1xuICAgICAgICBzd2lwZXIuc2xpZGVUbyhzbGlkZVRvSW5kZXgpO1xuICAgICAgfSk7XG4gICAgfSBlbHNlIHtcbiAgICAgIHN3aXBlci5zbGlkZVRvKHNsaWRlVG9JbmRleCk7XG4gICAgfVxuICB9IGVsc2Uge1xuICAgIHN3aXBlci5zbGlkZVRvKHNsaWRlVG9JbmRleCk7XG4gIH1cbn1cblxudmFyIHNsaWRlID0ge1xuICBzbGlkZVRvLFxuICBzbGlkZVRvTG9vcCxcbiAgc2xpZGVOZXh0LFxuICBzbGlkZVByZXYsXG4gIHNsaWRlUmVzZXQsXG4gIHNsaWRlVG9DbG9zZXN0LFxuICBzbGlkZVRvQ2xpY2tlZFNsaWRlXG59O1xuXG5mdW5jdGlvbiBsb29wQ3JlYXRlKHNsaWRlUmVhbEluZGV4KSB7XG4gIGNvbnN0IHN3aXBlciA9IHRoaXM7XG4gIGNvbnN0IHtcbiAgICBwYXJhbXMsXG4gICAgc2xpZGVzRWxcbiAgfSA9IHN3aXBlcjtcbiAgaWYgKCFwYXJhbXMubG9vcCB8fCBzd2lwZXIudmlydHVhbCAmJiBzd2lwZXIucGFyYW1zLnZpcnR1YWwuZW5hYmxlZCkgcmV0dXJuO1xuICBjb25zdCBpbml0U2xpZGVzID0gKCkgPT4ge1xuICAgIGNvbnN0IHNsaWRlcyA9IGVsZW1lbnRDaGlsZHJlbihzbGlkZXNFbCwgYC4ke3BhcmFtcy5zbGlkZUNsYXNzfSwgc3dpcGVyLXNsaWRlYCk7XG4gICAgc2xpZGVzLmZvckVhY2goKGVsLCBpbmRleCkgPT4ge1xuICAgICAgZWwuc2V0QXR0cmlidXRlKCdkYXRhLXN3aXBlci1zbGlkZS1pbmRleCcsIGluZGV4KTtcbiAgICB9KTtcbiAgfTtcbiAgY29uc3QgZ3JpZEVuYWJsZWQgPSBzd2lwZXIuZ3JpZCAmJiBwYXJhbXMuZ3JpZCAmJiBwYXJhbXMuZ3JpZC5yb3dzID4gMTtcbiAgY29uc3Qgc2xpZGVzUGVyR3JvdXAgPSBwYXJhbXMuc2xpZGVzUGVyR3JvdXAgKiAoZ3JpZEVuYWJsZWQgPyBwYXJhbXMuZ3JpZC5yb3dzIDogMSk7XG4gIGNvbnN0IHNob3VsZEZpbGxHcm91cCA9IHN3aXBlci5zbGlkZXMubGVuZ3RoICUgc2xpZGVzUGVyR3JvdXAgIT09IDA7XG4gIGNvbnN0IHNob3VsZEZpbGxHcmlkID0gZ3JpZEVuYWJsZWQgJiYgc3dpcGVyLnNsaWRlcy5sZW5ndGggJSBwYXJhbXMuZ3JpZC5yb3dzICE9PSAwO1xuICBjb25zdCBhZGRCbGFua1NsaWRlcyA9IGFtb3VudE9mU2xpZGVzID0+IHtcbiAgICBmb3IgKGxldCBpID0gMDsgaSA8IGFtb3VudE9mU2xpZGVzOyBpICs9IDEpIHtcbiAgICAgIGNvbnN0IHNsaWRlRWwgPSBzd2lwZXIuaXNFbGVtZW50ID8gY3JlYXRlRWxlbWVudCgnc3dpcGVyLXNsaWRlJywgW3BhcmFtcy5zbGlkZUJsYW5rQ2xhc3NdKSA6IGNyZWF0ZUVsZW1lbnQoJ2RpdicsIFtwYXJhbXMuc2xpZGVDbGFzcywgcGFyYW1zLnNsaWRlQmxhbmtDbGFzc10pO1xuICAgICAgc3dpcGVyLnNsaWRlc0VsLmFwcGVuZChzbGlkZUVsKTtcbiAgICB9XG4gIH07XG4gIGlmIChzaG91bGRGaWxsR3JvdXApIHtcbiAgICBpZiAocGFyYW1zLmxvb3BBZGRCbGFua1NsaWRlcykge1xuICAgICAgY29uc3Qgc2xpZGVzVG9BZGQgPSBzbGlkZXNQZXJHcm91cCAtIHN3aXBlci5zbGlkZXMubGVuZ3RoICUgc2xpZGVzUGVyR3JvdXA7XG4gICAgICBhZGRCbGFua1NsaWRlcyhzbGlkZXNUb0FkZCk7XG4gICAgICBzd2lwZXIucmVjYWxjU2xpZGVzKCk7XG4gICAgICBzd2lwZXIudXBkYXRlU2xpZGVzKCk7XG4gICAgfSBlbHNlIHtcbiAgICAgIHNob3dXYXJuaW5nKCdTd2lwZXIgTG9vcCBXYXJuaW5nOiBUaGUgbnVtYmVyIG9mIHNsaWRlcyBpcyBub3QgZXZlbiB0byBzbGlkZXNQZXJHcm91cCwgbG9vcCBtb2RlIG1heSBub3QgZnVuY3Rpb24gcHJvcGVybHkuIFlvdSBuZWVkIHRvIGFkZCBtb3JlIHNsaWRlcyAob3IgbWFrZSBkdXBsaWNhdGVzLCBvciBlbXB0eSBzbGlkZXMpJyk7XG4gICAgfVxuICAgIGluaXRTbGlkZXMoKTtcbiAgfSBlbHNlIGlmIChzaG91bGRGaWxsR3JpZCkge1xuICAgIGlmIChwYXJhbXMubG9vcEFkZEJsYW5rU2xpZGVzKSB7XG4gICAgICBjb25zdCBzbGlkZXNUb0FkZCA9IHBhcmFtcy5ncmlkLnJvd3MgLSBzd2lwZXIuc2xpZGVzLmxlbmd0aCAlIHBhcmFtcy5ncmlkLnJvd3M7XG4gICAgICBhZGRCbGFua1NsaWRlcyhzbGlkZXNUb0FkZCk7XG4gICAgICBzd2lwZXIucmVjYWxjU2xpZGVzKCk7XG4gICAgICBzd2lwZXIudXBkYXRlU2xpZGVzKCk7XG4gICAgfSBlbHNlIHtcbiAgICAgIHNob3dXYXJuaW5nKCdTd2lwZXIgTG9vcCBXYXJuaW5nOiBUaGUgbnVtYmVyIG9mIHNsaWRlcyBpcyBub3QgZXZlbiB0byBncmlkLnJvd3MsIGxvb3AgbW9kZSBtYXkgbm90IGZ1bmN0aW9uIHByb3Blcmx5LiBZb3UgbmVlZCB0byBhZGQgbW9yZSBzbGlkZXMgKG9yIG1ha2UgZHVwbGljYXRlcywgb3IgZW1wdHkgc2xpZGVzKScpO1xuICAgIH1cbiAgICBpbml0U2xpZGVzKCk7XG4gIH0gZWxzZSB7XG4gICAgaW5pdFNsaWRlcygpO1xuICB9XG4gIHN3aXBlci5sb29wRml4KHtcbiAgICBzbGlkZVJlYWxJbmRleCxcbiAgICBkaXJlY3Rpb246IHBhcmFtcy5jZW50ZXJlZFNsaWRlcyA/IHVuZGVmaW5lZCA6ICduZXh0J1xuICB9KTtcbn1cblxuZnVuY3Rpb24gbG9vcEZpeChfdGVtcCkge1xuICBsZXQge1xuICAgIHNsaWRlUmVhbEluZGV4LFxuICAgIHNsaWRlVG8gPSB0cnVlLFxuICAgIGRpcmVjdGlvbixcbiAgICBzZXRUcmFuc2xhdGUsXG4gICAgYWN0aXZlU2xpZGVJbmRleCxcbiAgICBieUNvbnRyb2xsZXIsXG4gICAgYnlNb3VzZXdoZWVsXG4gIH0gPSBfdGVtcCA9PT0gdm9pZCAwID8ge30gOiBfdGVtcDtcbiAgY29uc3Qgc3dpcGVyID0gdGhpcztcbiAgaWYgKCFzd2lwZXIucGFyYW1zLmxvb3ApIHJldHVybjtcbiAgc3dpcGVyLmVtaXQoJ2JlZm9yZUxvb3BGaXgnKTtcbiAgY29uc3Qge1xuICAgIHNsaWRlcyxcbiAgICBhbGxvd1NsaWRlUHJldixcbiAgICBhbGxvd1NsaWRlTmV4dCxcbiAgICBzbGlkZXNFbCxcbiAgICBwYXJhbXNcbiAgfSA9IHN3aXBlcjtcbiAgY29uc3Qge1xuICAgIGNlbnRlcmVkU2xpZGVzXG4gIH0gPSBwYXJhbXM7XG4gIHN3aXBlci5hbGxvd1NsaWRlUHJldiA9IHRydWU7XG4gIHN3aXBlci5hbGxvd1NsaWRlTmV4dCA9IHRydWU7XG4gIGlmIChzd2lwZXIudmlydHVhbCAmJiBwYXJhbXMudmlydHVhbC5lbmFibGVkKSB7XG4gICAgaWYgKHNsaWRlVG8pIHtcbiAgICAgIGlmICghcGFyYW1zLmNlbnRlcmVkU2xpZGVzICYmIHN3aXBlci5zbmFwSW5kZXggPT09IDApIHtcbiAgICAgICAgc3dpcGVyLnNsaWRlVG8oc3dpcGVyLnZpcnR1YWwuc2xpZGVzLmxlbmd0aCwgMCwgZmFsc2UsIHRydWUpO1xuICAgICAgfSBlbHNlIGlmIChwYXJhbXMuY2VudGVyZWRTbGlkZXMgJiYgc3dpcGVyLnNuYXBJbmRleCA8IHBhcmFtcy5zbGlkZXNQZXJWaWV3KSB7XG4gICAgICAgIHN3aXBlci5zbGlkZVRvKHN3aXBlci52aXJ0dWFsLnNsaWRlcy5sZW5ndGggKyBzd2lwZXIuc25hcEluZGV4LCAwLCBmYWxzZSwgdHJ1ZSk7XG4gICAgICB9IGVsc2UgaWYgKHN3aXBlci5zbmFwSW5kZXggPT09IHN3aXBlci5zbmFwR3JpZC5sZW5ndGggLSAxKSB7XG4gICAgICAgIHN3aXBlci5zbGlkZVRvKHN3aXBlci52aXJ0dWFsLnNsaWRlc0JlZm9yZSwgMCwgZmFsc2UsIHRydWUpO1xuICAgICAgfVxuICAgIH1cbiAgICBzd2lwZXIuYWxsb3dTbGlkZVByZXYgPSBhbGxvd1NsaWRlUHJldjtcbiAgICBzd2lwZXIuYWxsb3dTbGlkZU5leHQgPSBhbGxvd1NsaWRlTmV4dDtcbiAgICBzd2lwZXIuZW1pdCgnbG9vcEZpeCcpO1xuICAgIHJldHVybjtcbiAgfVxuICBsZXQgc2xpZGVzUGVyVmlldyA9IHBhcmFtcy5zbGlkZXNQZXJWaWV3O1xuICBpZiAoc2xpZGVzUGVyVmlldyA9PT0gJ2F1dG8nKSB7XG4gICAgc2xpZGVzUGVyVmlldyA9IHN3aXBlci5zbGlkZXNQZXJWaWV3RHluYW1pYygpO1xuICB9IGVsc2Uge1xuICAgIHNsaWRlc1BlclZpZXcgPSBNYXRoLmNlaWwocGFyc2VGbG9hdChwYXJhbXMuc2xpZGVzUGVyVmlldywgMTApKTtcbiAgICBpZiAoY2VudGVyZWRTbGlkZXMgJiYgc2xpZGVzUGVyVmlldyAlIDIgPT09IDApIHtcbiAgICAgIHNsaWRlc1BlclZpZXcgPSBzbGlkZXNQZXJWaWV3ICsgMTtcbiAgICB9XG4gIH1cbiAgY29uc3Qgc2xpZGVzUGVyR3JvdXAgPSBwYXJhbXMuc2xpZGVzUGVyR3JvdXBBdXRvID8gc2xpZGVzUGVyVmlldyA6IHBhcmFtcy5zbGlkZXNQZXJHcm91cDtcbiAgbGV0IGxvb3BlZFNsaWRlcyA9IHNsaWRlc1Blckdyb3VwO1xuICBpZiAobG9vcGVkU2xpZGVzICUgc2xpZGVzUGVyR3JvdXAgIT09IDApIHtcbiAgICBsb29wZWRTbGlkZXMgKz0gc2xpZGVzUGVyR3JvdXAgLSBsb29wZWRTbGlkZXMgJSBzbGlkZXNQZXJHcm91cDtcbiAgfVxuICBsb29wZWRTbGlkZXMgKz0gcGFyYW1zLmxvb3BBZGRpdGlvbmFsU2xpZGVzO1xuICBzd2lwZXIubG9vcGVkU2xpZGVzID0gbG9vcGVkU2xpZGVzO1xuICBjb25zdCBncmlkRW5hYmxlZCA9IHN3aXBlci5ncmlkICYmIHBhcmFtcy5ncmlkICYmIHBhcmFtcy5ncmlkLnJvd3MgPiAxO1xuICBpZiAoc2xpZGVzLmxlbmd0aCA8IHNsaWRlc1BlclZpZXcgKyBsb29wZWRTbGlkZXMpIHtcbiAgICBzaG93V2FybmluZygnU3dpcGVyIExvb3AgV2FybmluZzogVGhlIG51bWJlciBvZiBzbGlkZXMgaXMgbm90IGVub3VnaCBmb3IgbG9vcCBtb2RlLCBpdCB3aWxsIGJlIGRpc2FibGVkIGFuZCBub3QgZnVuY3Rpb24gcHJvcGVybHkuIFlvdSBuZWVkIHRvIGFkZCBtb3JlIHNsaWRlcyAob3IgbWFrZSBkdXBsaWNhdGVzKSBvciBsb3dlciB0aGUgdmFsdWVzIG9mIHNsaWRlc1BlclZpZXcgYW5kIHNsaWRlc1Blckdyb3VwIHBhcmFtZXRlcnMnKTtcbiAgfSBlbHNlIGlmIChncmlkRW5hYmxlZCAmJiBwYXJhbXMuZ3JpZC5maWxsID09PSAncm93Jykge1xuICAgIHNob3dXYXJuaW5nKCdTd2lwZXIgTG9vcCBXYXJuaW5nOiBMb29wIG1vZGUgaXMgbm90IGNvbXBhdGlibGUgd2l0aCBncmlkLmZpbGwgPSBgcm93YCcpO1xuICB9XG4gIGNvbnN0IHByZXBlbmRTbGlkZXNJbmRleGVzID0gW107XG4gIGNvbnN0IGFwcGVuZFNsaWRlc0luZGV4ZXMgPSBbXTtcbiAgbGV0IGFjdGl2ZUluZGV4ID0gc3dpcGVyLmFjdGl2ZUluZGV4O1xuICBpZiAodHlwZW9mIGFjdGl2ZVNsaWRlSW5kZXggPT09ICd1bmRlZmluZWQnKSB7XG4gICAgYWN0aXZlU2xpZGVJbmRleCA9IHN3aXBlci5nZXRTbGlkZUluZGV4KHNsaWRlcy5maWx0ZXIoZWwgPT4gZWwuY2xhc3NMaXN0LmNvbnRhaW5zKHBhcmFtcy5zbGlkZUFjdGl2ZUNsYXNzKSlbMF0pO1xuICB9IGVsc2Uge1xuICAgIGFjdGl2ZUluZGV4ID0gYWN0aXZlU2xpZGVJbmRleDtcbiAgfVxuICBjb25zdCBpc05leHQgPSBkaXJlY3Rpb24gPT09ICduZXh0JyB8fCAhZGlyZWN0aW9uO1xuICBjb25zdCBpc1ByZXYgPSBkaXJlY3Rpb24gPT09ICdwcmV2JyB8fCAhZGlyZWN0aW9uO1xuICBsZXQgc2xpZGVzUHJlcGVuZGVkID0gMDtcbiAgbGV0IHNsaWRlc0FwcGVuZGVkID0gMDtcbiAgY29uc3QgY29scyA9IGdyaWRFbmFibGVkID8gTWF0aC5jZWlsKHNsaWRlcy5sZW5ndGggLyBwYXJhbXMuZ3JpZC5yb3dzKSA6IHNsaWRlcy5sZW5ndGg7XG4gIGNvbnN0IGFjdGl2ZUNvbEluZGV4ID0gZ3JpZEVuYWJsZWQgPyBzbGlkZXNbYWN0aXZlU2xpZGVJbmRleF0uY29sdW1uIDogYWN0aXZlU2xpZGVJbmRleDtcbiAgY29uc3QgYWN0aXZlQ29sSW5kZXhXaXRoU2hpZnQgPSBhY3RpdmVDb2xJbmRleCArIChjZW50ZXJlZFNsaWRlcyAmJiB0eXBlb2Ygc2V0VHJhbnNsYXRlID09PSAndW5kZWZpbmVkJyA/IC1zbGlkZXNQZXJWaWV3IC8gMiArIDAuNSA6IDApO1xuICAvLyBwcmVwZW5kIGxhc3Qgc2xpZGVzIGJlZm9yZSBzdGFydFxuICBpZiAoYWN0aXZlQ29sSW5kZXhXaXRoU2hpZnQgPCBsb29wZWRTbGlkZXMpIHtcbiAgICBzbGlkZXNQcmVwZW5kZWQgPSBNYXRoLm1heChsb29wZWRTbGlkZXMgLSBhY3RpdmVDb2xJbmRleFdpdGhTaGlmdCwgc2xpZGVzUGVyR3JvdXApO1xuICAgIGZvciAobGV0IGkgPSAwOyBpIDwgbG9vcGVkU2xpZGVzIC0gYWN0aXZlQ29sSW5kZXhXaXRoU2hpZnQ7IGkgKz0gMSkge1xuICAgICAgY29uc3QgaW5kZXggPSBpIC0gTWF0aC5mbG9vcihpIC8gY29scykgKiBjb2xzO1xuICAgICAgaWYgKGdyaWRFbmFibGVkKSB7XG4gICAgICAgIGNvbnN0IGNvbEluZGV4VG9QcmVwZW5kID0gY29scyAtIGluZGV4IC0gMTtcbiAgICAgICAgZm9yIChsZXQgaSA9IHNsaWRlcy5sZW5ndGggLSAxOyBpID49IDA7IGkgLT0gMSkge1xuICAgICAgICAgIGlmIChzbGlkZXNbaV0uY29sdW1uID09PSBjb2xJbmRleFRvUHJlcGVuZCkgcHJlcGVuZFNsaWRlc0luZGV4ZXMucHVzaChpKTtcbiAgICAgICAgfVxuICAgICAgICAvLyBzbGlkZXMuZm9yRWFjaCgoc2xpZGUsIHNsaWRlSW5kZXgpID0+IHtcbiAgICAgICAgLy8gICBpZiAoc2xpZGUuY29sdW1uID09PSBjb2xJbmRleFRvUHJlcGVuZCkgcHJlcGVuZFNsaWRlc0luZGV4ZXMucHVzaChzbGlkZUluZGV4KTtcbiAgICAgICAgLy8gfSk7XG4gICAgICB9IGVsc2Uge1xuICAgICAgICBwcmVwZW5kU2xpZGVzSW5kZXhlcy5wdXNoKGNvbHMgLSBpbmRleCAtIDEpO1xuICAgICAgfVxuICAgIH1cbiAgfSBlbHNlIGlmIChhY3RpdmVDb2xJbmRleFdpdGhTaGlmdCArIHNsaWRlc1BlclZpZXcgPiBjb2xzIC0gbG9vcGVkU2xpZGVzKSB7XG4gICAgc2xpZGVzQXBwZW5kZWQgPSBNYXRoLm1heChhY3RpdmVDb2xJbmRleFdpdGhTaGlmdCAtIChjb2xzIC0gbG9vcGVkU2xpZGVzICogMiksIHNsaWRlc1Blckdyb3VwKTtcbiAgICBmb3IgKGxldCBpID0gMDsgaSA8IHNsaWRlc0FwcGVuZGVkOyBpICs9IDEpIHtcbiAgICAgIGNvbnN0IGluZGV4ID0gaSAtIE1hdGguZmxvb3IoaSAvIGNvbHMpICogY29scztcbiAgICAgIGlmIChncmlkRW5hYmxlZCkge1xuICAgICAgICBzbGlkZXMuZm9yRWFjaCgoc2xpZGUsIHNsaWRlSW5kZXgpID0+IHtcbiAgICAgICAgICBpZiAoc2xpZGUuY29sdW1uID09PSBpbmRleCkgYXBwZW5kU2xpZGVzSW5kZXhlcy5wdXNoKHNsaWRlSW5kZXgpO1xuICAgICAgICB9KTtcbiAgICAgIH0gZWxzZSB7XG4gICAgICAgIGFwcGVuZFNsaWRlc0luZGV4ZXMucHVzaChpbmRleCk7XG4gICAgICB9XG4gICAgfVxuICB9XG4gIHN3aXBlci5fX3ByZXZlbnRPYnNlcnZlcl9fID0gdHJ1ZTtcbiAgcmVxdWVzdEFuaW1hdGlvbkZyYW1lKCgpID0+IHtcbiAgICBzd2lwZXIuX19wcmV2ZW50T2JzZXJ2ZXJfXyA9IGZhbHNlO1xuICB9KTtcbiAgaWYgKGlzUHJldikge1xuICAgIHByZXBlbmRTbGlkZXNJbmRleGVzLmZvckVhY2goaW5kZXggPT4ge1xuICAgICAgc2xpZGVzW2luZGV4XS5zd2lwZXJMb29wTW92ZURPTSA9IHRydWU7XG4gICAgICBzbGlkZXNFbC5wcmVwZW5kKHNsaWRlc1tpbmRleF0pO1xuICAgICAgc2xpZGVzW2luZGV4XS5zd2lwZXJMb29wTW92ZURPTSA9IGZhbHNlO1xuICAgIH0pO1xuICB9XG4gIGlmIChpc05leHQpIHtcbiAgICBhcHBlbmRTbGlkZXNJbmRleGVzLmZvckVhY2goaW5kZXggPT4ge1xuICAgICAgc2xpZGVzW2luZGV4XS5zd2lwZXJMb29wTW92ZURPTSA9IHRydWU7XG4gICAgICBzbGlkZXNFbC5hcHBlbmQoc2xpZGVzW2luZGV4XSk7XG4gICAgICBzbGlkZXNbaW5kZXhdLnN3aXBlckxvb3BNb3ZlRE9NID0gZmFsc2U7XG4gICAgfSk7XG4gIH1cbiAgc3dpcGVyLnJlY2FsY1NsaWRlcygpO1xuICBpZiAocGFyYW1zLnNsaWRlc1BlclZpZXcgPT09ICdhdXRvJykge1xuICAgIHN3aXBlci51cGRhdGVTbGlkZXMoKTtcbiAgfSBlbHNlIGlmIChncmlkRW5hYmxlZCAmJiAocHJlcGVuZFNsaWRlc0luZGV4ZXMubGVuZ3RoID4gMCAmJiBpc1ByZXYgfHwgYXBwZW5kU2xpZGVzSW5kZXhlcy5sZW5ndGggPiAwICYmIGlzTmV4dCkpIHtcbiAgICBzd2lwZXIuc2xpZGVzLmZvckVhY2goKHNsaWRlLCBzbGlkZUluZGV4KSA9PiB7XG4gICAgICBzd2lwZXIuZ3JpZC51cGRhdGVTbGlkZShzbGlkZUluZGV4LCBzbGlkZSwgc3dpcGVyLnNsaWRlcyk7XG4gICAgfSk7XG4gIH1cbiAgaWYgKHBhcmFtcy53YXRjaFNsaWRlc1Byb2dyZXNzKSB7XG4gICAgc3dpcGVyLnVwZGF0ZVNsaWRlc09mZnNldCgpO1xuICB9XG4gIGlmIChzbGlkZVRvKSB7XG4gICAgaWYgKHByZXBlbmRTbGlkZXNJbmRleGVzLmxlbmd0aCA+IDAgJiYgaXNQcmV2KSB7XG4gICAgICBpZiAodHlwZW9mIHNsaWRlUmVhbEluZGV4ID09PSAndW5kZWZpbmVkJykge1xuICAgICAgICBjb25zdCBjdXJyZW50U2xpZGVUcmFuc2xhdGUgPSBzd2lwZXIuc2xpZGVzR3JpZFthY3RpdmVJbmRleF07XG4gICAgICAgIGNvbnN0IG5ld1NsaWRlVHJhbnNsYXRlID0gc3dpcGVyLnNsaWRlc0dyaWRbYWN0aXZlSW5kZXggKyBzbGlkZXNQcmVwZW5kZWRdO1xuICAgICAgICBjb25zdCBkaWZmID0gbmV3U2xpZGVUcmFuc2xhdGUgLSBjdXJyZW50U2xpZGVUcmFuc2xhdGU7XG4gICAgICAgIGlmIChieU1vdXNld2hlZWwpIHtcbiAgICAgICAgICBzd2lwZXIuc2V0VHJhbnNsYXRlKHN3aXBlci50cmFuc2xhdGUgLSBkaWZmKTtcbiAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICBzd2lwZXIuc2xpZGVUbyhhY3RpdmVJbmRleCArIE1hdGguY2VpbChzbGlkZXNQcmVwZW5kZWQpLCAwLCBmYWxzZSwgdHJ1ZSk7XG4gICAgICAgICAgaWYgKHNldFRyYW5zbGF0ZSkge1xuICAgICAgICAgICAgc3dpcGVyLnRvdWNoRXZlbnRzRGF0YS5zdGFydFRyYW5zbGF0ZSA9IHN3aXBlci50b3VjaEV2ZW50c0RhdGEuc3RhcnRUcmFuc2xhdGUgLSBkaWZmO1xuICAgICAgICAgICAgc3dpcGVyLnRvdWNoRXZlbnRzRGF0YS5jdXJyZW50VHJhbnNsYXRlID0gc3dpcGVyLnRvdWNoRXZlbnRzRGF0YS5jdXJyZW50VHJhbnNsYXRlIC0gZGlmZjtcbiAgICAgICAgICB9XG4gICAgICAgIH1cbiAgICAgIH0gZWxzZSB7XG4gICAgICAgIGlmIChzZXRUcmFuc2xhdGUpIHtcbiAgICAgICAgICBjb25zdCBzaGlmdCA9IGdyaWRFbmFibGVkID8gcHJlcGVuZFNsaWRlc0luZGV4ZXMubGVuZ3RoIC8gcGFyYW1zLmdyaWQucm93cyA6IHByZXBlbmRTbGlkZXNJbmRleGVzLmxlbmd0aDtcbiAgICAgICAgICBzd2lwZXIuc2xpZGVUbyhzd2lwZXIuYWN0aXZlSW5kZXggKyBzaGlmdCwgMCwgZmFsc2UsIHRydWUpO1xuICAgICAgICAgIHN3aXBlci50b3VjaEV2ZW50c0RhdGEuY3VycmVudFRyYW5zbGF0ZSA9IHN3aXBlci50cmFuc2xhdGU7XG4gICAgICAgIH1cbiAgICAgIH1cbiAgICB9IGVsc2UgaWYgKGFwcGVuZFNsaWRlc0luZGV4ZXMubGVuZ3RoID4gMCAmJiBpc05leHQpIHtcbiAgICAgIGlmICh0eXBlb2Ygc2xpZGVSZWFsSW5kZXggPT09ICd1bmRlZmluZWQnKSB7XG4gICAgICAgIGNvbnN0IGN1cnJlbnRTbGlkZVRyYW5zbGF0ZSA9IHN3aXBlci5zbGlkZXNHcmlkW2FjdGl2ZUluZGV4XTtcbiAgICAgICAgY29uc3QgbmV3U2xpZGVUcmFuc2xhdGUgPSBzd2lwZXIuc2xpZGVzR3JpZFthY3RpdmVJbmRleCAtIHNsaWRlc0FwcGVuZGVkXTtcbiAgICAgICAgY29uc3QgZGlmZiA9IG5ld1NsaWRlVHJhbnNsYXRlIC0gY3VycmVudFNsaWRlVHJhbnNsYXRlO1xuICAgICAgICBpZiAoYnlNb3VzZXdoZWVsKSB7XG4gICAgICAgICAgc3dpcGVyLnNldFRyYW5zbGF0ZShzd2lwZXIudHJhbnNsYXRlIC0gZGlmZik7XG4gICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgc3dpcGVyLnNsaWRlVG8oYWN0aXZlSW5kZXggLSBzbGlkZXNBcHBlbmRlZCwgMCwgZmFsc2UsIHRydWUpO1xuICAgICAgICAgIGlmIChzZXRUcmFuc2xhdGUpIHtcbiAgICAgICAgICAgIHN3aXBlci50b3VjaEV2ZW50c0RhdGEuc3RhcnRUcmFuc2xhdGUgPSBzd2lwZXIudG91Y2hFdmVudHNEYXRhLnN0YXJ0VHJhbnNsYXRlIC0gZGlmZjtcbiAgICAgICAgICAgIHN3aXBlci50b3VjaEV2ZW50c0RhdGEuY3VycmVudFRyYW5zbGF0ZSA9IHN3aXBlci50b3VjaEV2ZW50c0RhdGEuY3VycmVudFRyYW5zbGF0ZSAtIGRpZmY7XG4gICAgICAgICAgfVxuICAgICAgICB9XG4gICAgICB9IGVsc2Uge1xuICAgICAgICBjb25zdCBzaGlmdCA9IGdyaWRFbmFibGVkID8gYXBwZW5kU2xpZGVzSW5kZXhlcy5sZW5ndGggLyBwYXJhbXMuZ3JpZC5yb3dzIDogYXBwZW5kU2xpZGVzSW5kZXhlcy5sZW5ndGg7XG4gICAgICAgIHN3aXBlci5zbGlkZVRvKHN3aXBlci5hY3RpdmVJbmRleCAtIHNoaWZ0LCAwLCBmYWxzZSwgdHJ1ZSk7XG4gICAgICB9XG4gICAgfVxuICB9XG4gIHN3aXBlci5hbGxvd1NsaWRlUHJldiA9IGFsbG93U2xpZGVQcmV2O1xuICBzd2lwZXIuYWxsb3dTbGlkZU5leHQgPSBhbGxvd1NsaWRlTmV4dDtcbiAgaWYgKHN3aXBlci5jb250cm9sbGVyICYmIHN3aXBlci5jb250cm9sbGVyLmNvbnRyb2wgJiYgIWJ5Q29udHJvbGxlcikge1xuICAgIGNvbnN0IGxvb3BQYXJhbXMgPSB7XG4gICAgICBzbGlkZVJlYWxJbmRleCxcbiAgICAgIGRpcmVjdGlvbixcbiAgICAgIHNldFRyYW5zbGF0ZSxcbiAgICAgIGFjdGl2ZVNsaWRlSW5kZXgsXG4gICAgICBieUNvbnRyb2xsZXI6IHRydWVcbiAgICB9O1xuICAgIGlmIChBcnJheS5pc0FycmF5KHN3aXBlci5jb250cm9sbGVyLmNvbnRyb2wpKSB7XG4gICAgICBzd2lwZXIuY29udHJvbGxlci5jb250cm9sLmZvckVhY2goYyA9PiB7XG4gICAgICAgIGlmICghYy5kZXN0cm95ZWQgJiYgYy5wYXJhbXMubG9vcCkgYy5sb29wRml4KHtcbiAgICAgICAgICAuLi5sb29wUGFyYW1zLFxuICAgICAgICAgIHNsaWRlVG86IGMucGFyYW1zLnNsaWRlc1BlclZpZXcgPT09IHBhcmFtcy5zbGlkZXNQZXJWaWV3ID8gc2xpZGVUbyA6IGZhbHNlXG4gICAgICAgIH0pO1xuICAgICAgfSk7XG4gICAgfSBlbHNlIGlmIChzd2lwZXIuY29udHJvbGxlci5jb250cm9sIGluc3RhbmNlb2Ygc3dpcGVyLmNvbnN0cnVjdG9yICYmIHN3aXBlci5jb250cm9sbGVyLmNvbnRyb2wucGFyYW1zLmxvb3ApIHtcbiAgICAgIHN3aXBlci5jb250cm9sbGVyLmNvbnRyb2wubG9vcEZpeCh7XG4gICAgICAgIC4uLmxvb3BQYXJhbXMsXG4gICAgICAgIHNsaWRlVG86IHN3aXBlci5jb250cm9sbGVyLmNvbnRyb2wucGFyYW1zLnNsaWRlc1BlclZpZXcgPT09IHBhcmFtcy5zbGlkZXNQZXJWaWV3ID8gc2xpZGVUbyA6IGZhbHNlXG4gICAgICB9KTtcbiAgICB9XG4gIH1cbiAgc3dpcGVyLmVtaXQoJ2xvb3BGaXgnKTtcbn1cblxuZnVuY3Rpb24gbG9vcERlc3Ryb3koKSB7XG4gIGNvbnN0IHN3aXBlciA9IHRoaXM7XG4gIGNvbnN0IHtcbiAgICBwYXJhbXMsXG4gICAgc2xpZGVzRWxcbiAgfSA9IHN3aXBlcjtcbiAgaWYgKCFwYXJhbXMubG9vcCB8fCBzd2lwZXIudmlydHVhbCAmJiBzd2lwZXIucGFyYW1zLnZpcnR1YWwuZW5hYmxlZCkgcmV0dXJuO1xuICBzd2lwZXIucmVjYWxjU2xpZGVzKCk7XG4gIGNvbnN0IG5ld1NsaWRlc09yZGVyID0gW107XG4gIHN3aXBlci5zbGlkZXMuZm9yRWFjaChzbGlkZUVsID0+IHtcbiAgICBjb25zdCBpbmRleCA9IHR5cGVvZiBzbGlkZUVsLnN3aXBlclNsaWRlSW5kZXggPT09ICd1bmRlZmluZWQnID8gc2xpZGVFbC5nZXRBdHRyaWJ1dGUoJ2RhdGEtc3dpcGVyLXNsaWRlLWluZGV4JykgKiAxIDogc2xpZGVFbC5zd2lwZXJTbGlkZUluZGV4O1xuICAgIG5ld1NsaWRlc09yZGVyW2luZGV4XSA9IHNsaWRlRWw7XG4gIH0pO1xuICBzd2lwZXIuc2xpZGVzLmZvckVhY2goc2xpZGVFbCA9PiB7XG4gICAgc2xpZGVFbC5yZW1vdmVBdHRyaWJ1dGUoJ2RhdGEtc3dpcGVyLXNsaWRlLWluZGV4Jyk7XG4gIH0pO1xuICBuZXdTbGlkZXNPcmRlci5mb3JFYWNoKHNsaWRlRWwgPT4ge1xuICAgIHNsaWRlc0VsLmFwcGVuZChzbGlkZUVsKTtcbiAgfSk7XG4gIHN3aXBlci5yZWNhbGNTbGlkZXMoKTtcbiAgc3dpcGVyLnNsaWRlVG8oc3dpcGVyLnJlYWxJbmRleCwgMCk7XG59XG5cbnZhciBsb29wID0ge1xuICBsb29wQ3JlYXRlLFxuICBsb29wRml4LFxuICBsb29wRGVzdHJveVxufTtcblxuZnVuY3Rpb24gc2V0R3JhYkN1cnNvcihtb3ZpbmcpIHtcbiAgY29uc3Qgc3dpcGVyID0gdGhpcztcbiAgaWYgKCFzd2lwZXIucGFyYW1zLnNpbXVsYXRlVG91Y2ggfHwgc3dpcGVyLnBhcmFtcy53YXRjaE92ZXJmbG93ICYmIHN3aXBlci5pc0xvY2tlZCB8fCBzd2lwZXIucGFyYW1zLmNzc01vZGUpIHJldHVybjtcbiAgY29uc3QgZWwgPSBzd2lwZXIucGFyYW1zLnRvdWNoRXZlbnRzVGFyZ2V0ID09PSAnY29udGFpbmVyJyA/IHN3aXBlci5lbCA6IHN3aXBlci53cmFwcGVyRWw7XG4gIGlmIChzd2lwZXIuaXNFbGVtZW50KSB7XG4gICAgc3dpcGVyLl9fcHJldmVudE9ic2VydmVyX18gPSB0cnVlO1xuICB9XG4gIGVsLnN0eWxlLmN1cnNvciA9ICdtb3ZlJztcbiAgZWwuc3R5bGUuY3Vyc29yID0gbW92aW5nID8gJ2dyYWJiaW5nJyA6ICdncmFiJztcbiAgaWYgKHN3aXBlci5pc0VsZW1lbnQpIHtcbiAgICByZXF1ZXN0QW5pbWF0aW9uRnJhbWUoKCkgPT4ge1xuICAgICAgc3dpcGVyLl9fcHJldmVudE9ic2VydmVyX18gPSBmYWxzZTtcbiAgICB9KTtcbiAgfVxufVxuXG5mdW5jdGlvbiB1bnNldEdyYWJDdXJzb3IoKSB7XG4gIGNvbnN0IHN3aXBlciA9IHRoaXM7XG4gIGlmIChzd2lwZXIucGFyYW1zLndhdGNoT3ZlcmZsb3cgJiYgc3dpcGVyLmlzTG9ja2VkIHx8IHN3aXBlci5wYXJhbXMuY3NzTW9kZSkge1xuICAgIHJldHVybjtcbiAgfVxuICBpZiAoc3dpcGVyLmlzRWxlbWVudCkge1xuICAgIHN3aXBlci5fX3ByZXZlbnRPYnNlcnZlcl9fID0gdHJ1ZTtcbiAgfVxuICBzd2lwZXJbc3dpcGVyLnBhcmFtcy50b3VjaEV2ZW50c1RhcmdldCA9PT0gJ2NvbnRhaW5lcicgPyAnZWwnIDogJ3dyYXBwZXJFbCddLnN0eWxlLmN1cnNvciA9ICcnO1xuICBpZiAoc3dpcGVyLmlzRWxlbWVudCkge1xuICAgIHJlcXVlc3RBbmltYXRpb25GcmFtZSgoKSA9PiB7XG4gICAgICBzd2lwZXIuX19wcmV2ZW50T2JzZXJ2ZXJfXyA9IGZhbHNlO1xuICAgIH0pO1xuICB9XG59XG5cbnZhciBncmFiQ3Vyc29yID0ge1xuICBzZXRHcmFiQ3Vyc29yLFxuICB1bnNldEdyYWJDdXJzb3Jcbn07XG5cbi8vIE1vZGlmaWVkIGZyb20gaHR0cHM6Ly9zdGFja292ZXJmbG93LmNvbS9xdWVzdGlvbnMvNTQ1MjA1NTQvY3VzdG9tLWVsZW1lbnQtZ2V0cm9vdG5vZGUtY2xvc2VzdC1mdW5jdGlvbi1jcm9zc2luZy1tdWx0aXBsZS1wYXJlbnQtc2hhZG93ZFxuZnVuY3Rpb24gY2xvc2VzdEVsZW1lbnQoc2VsZWN0b3IsIGJhc2UpIHtcbiAgaWYgKGJhc2UgPT09IHZvaWQgMCkge1xuICAgIGJhc2UgPSB0aGlzO1xuICB9XG4gIGZ1bmN0aW9uIF9fY2xvc2VzdEZyb20oZWwpIHtcbiAgICBpZiAoIWVsIHx8IGVsID09PSBnZXREb2N1bWVudCgpIHx8IGVsID09PSBnZXRXaW5kb3coKSkgcmV0dXJuIG51bGw7XG4gICAgaWYgKGVsLmFzc2lnbmVkU2xvdCkgZWwgPSBlbC5hc3NpZ25lZFNsb3Q7XG4gICAgY29uc3QgZm91bmQgPSBlbC5jbG9zZXN0KHNlbGVjdG9yKTtcbiAgICBpZiAoIWZvdW5kICYmICFlbC5nZXRSb290Tm9kZSkge1xuICAgICAgcmV0dXJuIG51bGw7XG4gICAgfVxuICAgIHJldHVybiBmb3VuZCB8fCBfX2Nsb3Nlc3RGcm9tKGVsLmdldFJvb3ROb2RlKCkuaG9zdCk7XG4gIH1cbiAgcmV0dXJuIF9fY2xvc2VzdEZyb20oYmFzZSk7XG59XG5mdW5jdGlvbiBwcmV2ZW50RWRnZVN3aXBlKHN3aXBlciwgZXZlbnQsIHN0YXJ0WCkge1xuICBjb25zdCB3aW5kb3cgPSBnZXRXaW5kb3coKTtcbiAgY29uc3Qge1xuICAgIHBhcmFtc1xuICB9ID0gc3dpcGVyO1xuICBjb25zdCBlZGdlU3dpcGVEZXRlY3Rpb24gPSBwYXJhbXMuZWRnZVN3aXBlRGV0ZWN0aW9uO1xuICBjb25zdCBlZGdlU3dpcGVUaHJlc2hvbGQgPSBwYXJhbXMuZWRnZVN3aXBlVGhyZXNob2xkO1xuICBpZiAoZWRnZVN3aXBlRGV0ZWN0aW9uICYmIChzdGFydFggPD0gZWRnZVN3aXBlVGhyZXNob2xkIHx8IHN0YXJ0WCA+PSB3aW5kb3cuaW5uZXJXaWR0aCAtIGVkZ2VTd2lwZVRocmVzaG9sZCkpIHtcbiAgICBpZiAoZWRnZVN3aXBlRGV0ZWN0aW9uID09PSAncHJldmVudCcpIHtcbiAgICAgIGV2ZW50LnByZXZlbnREZWZhdWx0KCk7XG4gICAgICByZXR1cm4gdHJ1ZTtcbiAgICB9XG4gICAgcmV0dXJuIGZhbHNlO1xuICB9XG4gIHJldHVybiB0cnVlO1xufVxuZnVuY3Rpb24gb25Ub3VjaFN0YXJ0KGV2ZW50KSB7XG4gIGNvbnN0IHN3aXBlciA9IHRoaXM7XG4gIGNvbnN0IGRvY3VtZW50ID0gZ2V0RG9jdW1lbnQoKTtcbiAgbGV0IGUgPSBldmVudDtcbiAgaWYgKGUub3JpZ2luYWxFdmVudCkgZSA9IGUub3JpZ2luYWxFdmVudDtcbiAgY29uc3QgZGF0YSA9IHN3aXBlci50b3VjaEV2ZW50c0RhdGE7XG4gIGlmIChlLnR5cGUgPT09ICdwb2ludGVyZG93bicpIHtcbiAgICBpZiAoZGF0YS5wb2ludGVySWQgIT09IG51bGwgJiYgZGF0YS5wb2ludGVySWQgIT09IGUucG9pbnRlcklkKSB7XG4gICAgICByZXR1cm47XG4gICAgfVxuICAgIGRhdGEucG9pbnRlcklkID0gZS5wb2ludGVySWQ7XG4gIH0gZWxzZSBpZiAoZS50eXBlID09PSAndG91Y2hzdGFydCcgJiYgZS50YXJnZXRUb3VjaGVzLmxlbmd0aCA9PT0gMSkge1xuICAgIGRhdGEudG91Y2hJZCA9IGUudGFyZ2V0VG91Y2hlc1swXS5pZGVudGlmaWVyO1xuICB9XG4gIGlmIChlLnR5cGUgPT09ICd0b3VjaHN0YXJ0Jykge1xuICAgIC8vIGRvbid0IHByb2NlZWQgdG91Y2ggZXZlbnRcbiAgICBwcmV2ZW50RWRnZVN3aXBlKHN3aXBlciwgZSwgZS50YXJnZXRUb3VjaGVzWzBdLnBhZ2VYKTtcbiAgICByZXR1cm47XG4gIH1cbiAgY29uc3Qge1xuICAgIHBhcmFtcyxcbiAgICB0b3VjaGVzLFxuICAgIGVuYWJsZWRcbiAgfSA9IHN3aXBlcjtcbiAgaWYgKCFlbmFibGVkKSByZXR1cm47XG4gIGlmICghcGFyYW1zLnNpbXVsYXRlVG91Y2ggJiYgZS5wb2ludGVyVHlwZSA9PT0gJ21vdXNlJykgcmV0dXJuO1xuICBpZiAoc3dpcGVyLmFuaW1hdGluZyAmJiBwYXJhbXMucHJldmVudEludGVyYWN0aW9uT25UcmFuc2l0aW9uKSB7XG4gICAgcmV0dXJuO1xuICB9XG4gIGlmICghc3dpcGVyLmFuaW1hdGluZyAmJiBwYXJhbXMuY3NzTW9kZSAmJiBwYXJhbXMubG9vcCkge1xuICAgIHN3aXBlci5sb29wRml4KCk7XG4gIH1cbiAgbGV0IHRhcmdldEVsID0gZS50YXJnZXQ7XG4gIGlmIChwYXJhbXMudG91Y2hFdmVudHNUYXJnZXQgPT09ICd3cmFwcGVyJykge1xuICAgIGlmICghZWxlbWVudElzQ2hpbGRPZih0YXJnZXRFbCwgc3dpcGVyLndyYXBwZXJFbCkpIHJldHVybjtcbiAgfVxuICBpZiAoJ3doaWNoJyBpbiBlICYmIGUud2hpY2ggPT09IDMpIHJldHVybjtcbiAgaWYgKCdidXR0b24nIGluIGUgJiYgZS5idXR0b24gPiAwKSByZXR1cm47XG4gIGlmIChkYXRhLmlzVG91Y2hlZCAmJiBkYXRhLmlzTW92ZWQpIHJldHVybjtcblxuICAvLyBjaGFuZ2UgdGFyZ2V0IGVsIGZvciBzaGFkb3cgcm9vdCBjb21wb25lbnRcbiAgY29uc3Qgc3dpcGluZ0NsYXNzSGFzVmFsdWUgPSAhIXBhcmFtcy5ub1N3aXBpbmdDbGFzcyAmJiBwYXJhbXMubm9Td2lwaW5nQ2xhc3MgIT09ICcnO1xuICAvLyBlc2xpbnQtZGlzYWJsZS1uZXh0LWxpbmVcbiAgY29uc3QgZXZlbnRQYXRoID0gZS5jb21wb3NlZFBhdGggPyBlLmNvbXBvc2VkUGF0aCgpIDogZS5wYXRoO1xuICBpZiAoc3dpcGluZ0NsYXNzSGFzVmFsdWUgJiYgZS50YXJnZXQgJiYgZS50YXJnZXQuc2hhZG93Um9vdCAmJiBldmVudFBhdGgpIHtcbiAgICB0YXJnZXRFbCA9IGV2ZW50UGF0aFswXTtcbiAgfVxuICBjb25zdCBub1N3aXBpbmdTZWxlY3RvciA9IHBhcmFtcy5ub1N3aXBpbmdTZWxlY3RvciA/IHBhcmFtcy5ub1N3aXBpbmdTZWxlY3RvciA6IGAuJHtwYXJhbXMubm9Td2lwaW5nQ2xhc3N9YDtcbiAgY29uc3QgaXNUYXJnZXRTaGFkb3cgPSAhIShlLnRhcmdldCAmJiBlLnRhcmdldC5zaGFkb3dSb290KTtcblxuICAvLyB1c2UgY2xvc2VzdEVsZW1lbnQgZm9yIHNoYWRvdyByb290IGVsZW1lbnQgdG8gZ2V0IHRoZSBhY3R1YWwgY2xvc2VzdCBmb3IgbmVzdGVkIHNoYWRvdyByb290IGVsZW1lbnRcbiAgaWYgKHBhcmFtcy5ub1N3aXBpbmcgJiYgKGlzVGFyZ2V0U2hhZG93ID8gY2xvc2VzdEVsZW1lbnQobm9Td2lwaW5nU2VsZWN0b3IsIHRhcmdldEVsKSA6IHRhcmdldEVsLmNsb3Nlc3Qobm9Td2lwaW5nU2VsZWN0b3IpKSkge1xuICAgIHN3aXBlci5hbGxvd0NsaWNrID0gdHJ1ZTtcbiAgICByZXR1cm47XG4gIH1cbiAgaWYgKHBhcmFtcy5zd2lwZUhhbmRsZXIpIHtcbiAgICBpZiAoIXRhcmdldEVsLmNsb3Nlc3QocGFyYW1zLnN3aXBlSGFuZGxlcikpIHJldHVybjtcbiAgfVxuICB0b3VjaGVzLmN1cnJlbnRYID0gZS5wYWdlWDtcbiAgdG91Y2hlcy5jdXJyZW50WSA9IGUucGFnZVk7XG4gIGNvbnN0IHN0YXJ0WCA9IHRvdWNoZXMuY3VycmVudFg7XG4gIGNvbnN0IHN0YXJ0WSA9IHRvdWNoZXMuY3VycmVudFk7XG5cbiAgLy8gRG8gTk9UIHN0YXJ0IGlmIGlPUyBlZGdlIHN3aXBlIGlzIGRldGVjdGVkLiBPdGhlcndpc2UgaU9TIGFwcCBjYW5ub3Qgc3dpcGUtdG8tZ28tYmFjayBhbnltb3JlXG5cbiAgaWYgKCFwcmV2ZW50RWRnZVN3aXBlKHN3aXBlciwgZSwgc3RhcnRYKSkge1xuICAgIHJldHVybjtcbiAgfVxuICBPYmplY3QuYXNzaWduKGRhdGEsIHtcbiAgICBpc1RvdWNoZWQ6IHRydWUsXG4gICAgaXNNb3ZlZDogZmFsc2UsXG4gICAgYWxsb3dUb3VjaENhbGxiYWNrczogdHJ1ZSxcbiAgICBpc1Njcm9sbGluZzogdW5kZWZpbmVkLFxuICAgIHN0YXJ0TW92aW5nOiB1bmRlZmluZWRcbiAgfSk7XG4gIHRvdWNoZXMuc3RhcnRYID0gc3RhcnRYO1xuICB0b3VjaGVzLnN0YXJ0WSA9IHN0YXJ0WTtcbiAgZGF0YS50b3VjaFN0YXJ0VGltZSA9IG5vdygpO1xuICBzd2lwZXIuYWxsb3dDbGljayA9IHRydWU7XG4gIHN3aXBlci51cGRhdGVTaXplKCk7XG4gIHN3aXBlci5zd2lwZURpcmVjdGlvbiA9IHVuZGVmaW5lZDtcbiAgaWYgKHBhcmFtcy50aHJlc2hvbGQgPiAwKSBkYXRhLmFsbG93VGhyZXNob2xkTW92ZSA9IGZhbHNlO1xuICBsZXQgcHJldmVudERlZmF1bHQgPSB0cnVlO1xuICBpZiAodGFyZ2V0RWwubWF0Y2hlcyhkYXRhLmZvY3VzYWJsZUVsZW1lbnRzKSkge1xuICAgIHByZXZlbnREZWZhdWx0ID0gZmFsc2U7XG4gICAgaWYgKHRhcmdldEVsLm5vZGVOYW1lID09PSAnU0VMRUNUJykge1xuICAgICAgZGF0YS5pc1RvdWNoZWQgPSBmYWxzZTtcbiAgICB9XG4gIH1cbiAgaWYgKGRvY3VtZW50LmFjdGl2ZUVsZW1lbnQgJiYgZG9jdW1lbnQuYWN0aXZlRWxlbWVudC5tYXRjaGVzKGRhdGEuZm9jdXNhYmxlRWxlbWVudHMpICYmIGRvY3VtZW50LmFjdGl2ZUVsZW1lbnQgIT09IHRhcmdldEVsICYmIChlLnBvaW50ZXJUeXBlID09PSAnbW91c2UnIHx8IGUucG9pbnRlclR5cGUgIT09ICdtb3VzZScgJiYgIXRhcmdldEVsLm1hdGNoZXMoZGF0YS5mb2N1c2FibGVFbGVtZW50cykpKSB7XG4gICAgZG9jdW1lbnQuYWN0aXZlRWxlbWVudC5ibHVyKCk7XG4gIH1cbiAgY29uc3Qgc2hvdWxkUHJldmVudERlZmF1bHQgPSBwcmV2ZW50RGVmYXVsdCAmJiBzd2lwZXIuYWxsb3dUb3VjaE1vdmUgJiYgcGFyYW1zLnRvdWNoU3RhcnRQcmV2ZW50RGVmYXVsdDtcbiAgaWYgKChwYXJhbXMudG91Y2hTdGFydEZvcmNlUHJldmVudERlZmF1bHQgfHwgc2hvdWxkUHJldmVudERlZmF1bHQpICYmICF0YXJnZXRFbC5pc0NvbnRlbnRFZGl0YWJsZSkge1xuICAgIGUucHJldmVudERlZmF1bHQoKTtcbiAgfVxuICBpZiAocGFyYW1zLmZyZWVNb2RlICYmIHBhcmFtcy5mcmVlTW9kZS5lbmFibGVkICYmIHN3aXBlci5mcmVlTW9kZSAmJiBzd2lwZXIuYW5pbWF0aW5nICYmICFwYXJhbXMuY3NzTW9kZSkge1xuICAgIHN3aXBlci5mcmVlTW9kZS5vblRvdWNoU3RhcnQoKTtcbiAgfVxuICBzd2lwZXIuZW1pdCgndG91Y2hTdGFydCcsIGUpO1xufVxuXG5mdW5jdGlvbiBvblRvdWNoTW92ZShldmVudCkge1xuICBjb25zdCBkb2N1bWVudCA9IGdldERvY3VtZW50KCk7XG4gIGNvbnN0IHN3aXBlciA9IHRoaXM7XG4gIGNvbnN0IGRhdGEgPSBzd2lwZXIudG91Y2hFdmVudHNEYXRhO1xuICBjb25zdCB7XG4gICAgcGFyYW1zLFxuICAgIHRvdWNoZXMsXG4gICAgcnRsVHJhbnNsYXRlOiBydGwsXG4gICAgZW5hYmxlZFxuICB9ID0gc3dpcGVyO1xuICBpZiAoIWVuYWJsZWQpIHJldHVybjtcbiAgaWYgKCFwYXJhbXMuc2ltdWxhdGVUb3VjaCAmJiBldmVudC5wb2ludGVyVHlwZSA9PT0gJ21vdXNlJykgcmV0dXJuO1xuICBsZXQgZSA9IGV2ZW50O1xuICBpZiAoZS5vcmlnaW5hbEV2ZW50KSBlID0gZS5vcmlnaW5hbEV2ZW50O1xuICBpZiAoZS50eXBlID09PSAncG9pbnRlcm1vdmUnKSB7XG4gICAgaWYgKGRhdGEudG91Y2hJZCAhPT0gbnVsbCkgcmV0dXJuOyAvLyByZXR1cm4gZnJvbSBwb2ludGVyIGlmIHdlIHVzZSB0b3VjaFxuICAgIGNvbnN0IGlkID0gZS5wb2ludGVySWQ7XG4gICAgaWYgKGlkICE9PSBkYXRhLnBvaW50ZXJJZCkgcmV0dXJuO1xuICB9XG4gIGxldCB0YXJnZXRUb3VjaDtcbiAgaWYgKGUudHlwZSA9PT0gJ3RvdWNobW92ZScpIHtcbiAgICB0YXJnZXRUb3VjaCA9IFsuLi5lLmNoYW5nZWRUb3VjaGVzXS5maWx0ZXIodCA9PiB0LmlkZW50aWZpZXIgPT09IGRhdGEudG91Y2hJZClbMF07XG4gICAgaWYgKCF0YXJnZXRUb3VjaCB8fCB0YXJnZXRUb3VjaC5pZGVudGlmaWVyICE9PSBkYXRhLnRvdWNoSWQpIHJldHVybjtcbiAgfSBlbHNlIHtcbiAgICB0YXJnZXRUb3VjaCA9IGU7XG4gIH1cbiAgaWYgKCFkYXRhLmlzVG91Y2hlZCkge1xuICAgIGlmIChkYXRhLnN0YXJ0TW92aW5nICYmIGRhdGEuaXNTY3JvbGxpbmcpIHtcbiAgICAgIHN3aXBlci5lbWl0KCd0b3VjaE1vdmVPcHBvc2l0ZScsIGUpO1xuICAgIH1cbiAgICByZXR1cm47XG4gIH1cbiAgY29uc3QgcGFnZVggPSB0YXJnZXRUb3VjaC5wYWdlWDtcbiAgY29uc3QgcGFnZVkgPSB0YXJnZXRUb3VjaC5wYWdlWTtcbiAgaWYgKGUucHJldmVudGVkQnlOZXN0ZWRTd2lwZXIpIHtcbiAgICB0b3VjaGVzLnN0YXJ0WCA9IHBhZ2VYO1xuICAgIHRvdWNoZXMuc3RhcnRZID0gcGFnZVk7XG4gICAgcmV0dXJuO1xuICB9XG4gIGlmICghc3dpcGVyLmFsbG93VG91Y2hNb3ZlKSB7XG4gICAgaWYgKCFlLnRhcmdldC5tYXRjaGVzKGRhdGEuZm9jdXNhYmxlRWxlbWVudHMpKSB7XG4gICAgICBzd2lwZXIuYWxsb3dDbGljayA9IGZhbHNlO1xuICAgIH1cbiAgICBpZiAoZGF0YS5pc1RvdWNoZWQpIHtcbiAgICAgIE9iamVjdC5hc3NpZ24odG91Y2hlcywge1xuICAgICAgICBzdGFydFg6IHBhZ2VYLFxuICAgICAgICBzdGFydFk6IHBhZ2VZLFxuICAgICAgICBjdXJyZW50WDogcGFnZVgsXG4gICAgICAgIGN1cnJlbnRZOiBwYWdlWVxuICAgICAgfSk7XG4gICAgICBkYXRhLnRvdWNoU3RhcnRUaW1lID0gbm93KCk7XG4gICAgfVxuICAgIHJldHVybjtcbiAgfVxuICBpZiAocGFyYW1zLnRvdWNoUmVsZWFzZU9uRWRnZXMgJiYgIXBhcmFtcy5sb29wKSB7XG4gICAgaWYgKHN3aXBlci5pc1ZlcnRpY2FsKCkpIHtcbiAgICAgIC8vIFZlcnRpY2FsXG4gICAgICBpZiAocGFnZVkgPCB0b3VjaGVzLnN0YXJ0WSAmJiBzd2lwZXIudHJhbnNsYXRlIDw9IHN3aXBlci5tYXhUcmFuc2xhdGUoKSB8fCBwYWdlWSA+IHRvdWNoZXMuc3RhcnRZICYmIHN3aXBlci50cmFuc2xhdGUgPj0gc3dpcGVyLm1pblRyYW5zbGF0ZSgpKSB7XG4gICAgICAgIGRhdGEuaXNUb3VjaGVkID0gZmFsc2U7XG4gICAgICAgIGRhdGEuaXNNb3ZlZCA9IGZhbHNlO1xuICAgICAgICByZXR1cm47XG4gICAgICB9XG4gICAgfSBlbHNlIGlmIChwYWdlWCA8IHRvdWNoZXMuc3RhcnRYICYmIHN3aXBlci50cmFuc2xhdGUgPD0gc3dpcGVyLm1heFRyYW5zbGF0ZSgpIHx8IHBhZ2VYID4gdG91Y2hlcy5zdGFydFggJiYgc3dpcGVyLnRyYW5zbGF0ZSA+PSBzd2lwZXIubWluVHJhbnNsYXRlKCkpIHtcbiAgICAgIHJldHVybjtcbiAgICB9XG4gIH1cbiAgaWYgKGRvY3VtZW50LmFjdGl2ZUVsZW1lbnQgJiYgZG9jdW1lbnQuYWN0aXZlRWxlbWVudC5tYXRjaGVzKGRhdGEuZm9jdXNhYmxlRWxlbWVudHMpICYmIGRvY3VtZW50LmFjdGl2ZUVsZW1lbnQgIT09IGUudGFyZ2V0ICYmIGUucG9pbnRlclR5cGUgIT09ICdtb3VzZScpIHtcbiAgICBkb2N1bWVudC5hY3RpdmVFbGVtZW50LmJsdXIoKTtcbiAgfVxuICBpZiAoZG9jdW1lbnQuYWN0aXZlRWxlbWVudCkge1xuICAgIGlmIChlLnRhcmdldCA9PT0gZG9jdW1lbnQuYWN0aXZlRWxlbWVudCAmJiBlLnRhcmdldC5tYXRjaGVzKGRhdGEuZm9jdXNhYmxlRWxlbWVudHMpKSB7XG4gICAgICBkYXRhLmlzTW92ZWQgPSB0cnVlO1xuICAgICAgc3dpcGVyLmFsbG93Q2xpY2sgPSBmYWxzZTtcbiAgICAgIHJldHVybjtcbiAgICB9XG4gIH1cbiAgaWYgKGRhdGEuYWxsb3dUb3VjaENhbGxiYWNrcykge1xuICAgIHN3aXBlci5lbWl0KCd0b3VjaE1vdmUnLCBlKTtcbiAgfVxuICB0b3VjaGVzLnByZXZpb3VzWCA9IHRvdWNoZXMuY3VycmVudFg7XG4gIHRvdWNoZXMucHJldmlvdXNZID0gdG91Y2hlcy5jdXJyZW50WTtcbiAgdG91Y2hlcy5jdXJyZW50WCA9IHBhZ2VYO1xuICB0b3VjaGVzLmN1cnJlbnRZID0gcGFnZVk7XG4gIGNvbnN0IGRpZmZYID0gdG91Y2hlcy5jdXJyZW50WCAtIHRvdWNoZXMuc3RhcnRYO1xuICBjb25zdCBkaWZmWSA9IHRvdWNoZXMuY3VycmVudFkgLSB0b3VjaGVzLnN0YXJ0WTtcbiAgaWYgKHN3aXBlci5wYXJhbXMudGhyZXNob2xkICYmIE1hdGguc3FydChkaWZmWCAqKiAyICsgZGlmZlkgKiogMikgPCBzd2lwZXIucGFyYW1zLnRocmVzaG9sZCkgcmV0dXJuO1xuICBpZiAodHlwZW9mIGRhdGEuaXNTY3JvbGxpbmcgPT09ICd1bmRlZmluZWQnKSB7XG4gICAgbGV0IHRvdWNoQW5nbGU7XG4gICAgaWYgKHN3aXBlci5pc0hvcml6b250YWwoKSAmJiB0b3VjaGVzLmN1cnJlbnRZID09PSB0b3VjaGVzLnN0YXJ0WSB8fCBzd2lwZXIuaXNWZXJ0aWNhbCgpICYmIHRvdWNoZXMuY3VycmVudFggPT09IHRvdWNoZXMuc3RhcnRYKSB7XG4gICAgICBkYXRhLmlzU2Nyb2xsaW5nID0gZmFsc2U7XG4gICAgfSBlbHNlIHtcbiAgICAgIC8vIGVzbGludC1kaXNhYmxlLW5leHQtbGluZVxuICAgICAgaWYgKGRpZmZYICogZGlmZlggKyBkaWZmWSAqIGRpZmZZID49IDI1KSB7XG4gICAgICAgIHRvdWNoQW5nbGUgPSBNYXRoLmF0YW4yKE1hdGguYWJzKGRpZmZZKSwgTWF0aC5hYnMoZGlmZlgpKSAqIDE4MCAvIE1hdGguUEk7XG4gICAgICAgIGRhdGEuaXNTY3JvbGxpbmcgPSBzd2lwZXIuaXNIb3Jpem9udGFsKCkgPyB0b3VjaEFuZ2xlID4gcGFyYW1zLnRvdWNoQW5nbGUgOiA5MCAtIHRvdWNoQW5nbGUgPiBwYXJhbXMudG91Y2hBbmdsZTtcbiAgICAgIH1cbiAgICB9XG4gIH1cbiAgaWYgKGRhdGEuaXNTY3JvbGxpbmcpIHtcbiAgICBzd2lwZXIuZW1pdCgndG91Y2hNb3ZlT3Bwb3NpdGUnLCBlKTtcbiAgfVxuICBpZiAodHlwZW9mIGRhdGEuc3RhcnRNb3ZpbmcgPT09ICd1bmRlZmluZWQnKSB7XG4gICAgaWYgKHRvdWNoZXMuY3VycmVudFggIT09IHRvdWNoZXMuc3RhcnRYIHx8IHRvdWNoZXMuY3VycmVudFkgIT09IHRvdWNoZXMuc3RhcnRZKSB7XG4gICAgICBkYXRhLnN0YXJ0TW92aW5nID0gdHJ1ZTtcbiAgICB9XG4gIH1cbiAgaWYgKGRhdGEuaXNTY3JvbGxpbmcgfHwgZS50eXBlID09PSAndG91Y2htb3ZlJyAmJiBkYXRhLnByZXZlbnRUb3VjaE1vdmVGcm9tUG9pbnRlck1vdmUpIHtcbiAgICBkYXRhLmlzVG91Y2hlZCA9IGZhbHNlO1xuICAgIHJldHVybjtcbiAgfVxuICBpZiAoIWRhdGEuc3RhcnRNb3ZpbmcpIHtcbiAgICByZXR1cm47XG4gIH1cbiAgc3dpcGVyLmFsbG93Q2xpY2sgPSBmYWxzZTtcbiAgaWYgKCFwYXJhbXMuY3NzTW9kZSAmJiBlLmNhbmNlbGFibGUpIHtcbiAgICBlLnByZXZlbnREZWZhdWx0KCk7XG4gIH1cbiAgaWYgKHBhcmFtcy50b3VjaE1vdmVTdG9wUHJvcGFnYXRpb24gJiYgIXBhcmFtcy5uZXN0ZWQpIHtcbiAgICBlLnN0b3BQcm9wYWdhdGlvbigpO1xuICB9XG4gIGxldCBkaWZmID0gc3dpcGVyLmlzSG9yaXpvbnRhbCgpID8gZGlmZlggOiBkaWZmWTtcbiAgbGV0IHRvdWNoZXNEaWZmID0gc3dpcGVyLmlzSG9yaXpvbnRhbCgpID8gdG91Y2hlcy5jdXJyZW50WCAtIHRvdWNoZXMucHJldmlvdXNYIDogdG91Y2hlcy5jdXJyZW50WSAtIHRvdWNoZXMucHJldmlvdXNZO1xuICBpZiAocGFyYW1zLm9uZVdheU1vdmVtZW50KSB7XG4gICAgZGlmZiA9IE1hdGguYWJzKGRpZmYpICogKHJ0bCA/IDEgOiAtMSk7XG4gICAgdG91Y2hlc0RpZmYgPSBNYXRoLmFicyh0b3VjaGVzRGlmZikgKiAocnRsID8gMSA6IC0xKTtcbiAgfVxuICB0b3VjaGVzLmRpZmYgPSBkaWZmO1xuICBkaWZmICo9IHBhcmFtcy50b3VjaFJhdGlvO1xuICBpZiAocnRsKSB7XG4gICAgZGlmZiA9IC1kaWZmO1xuICAgIHRvdWNoZXNEaWZmID0gLXRvdWNoZXNEaWZmO1xuICB9XG4gIGNvbnN0IHByZXZUb3VjaGVzRGlyZWN0aW9uID0gc3dpcGVyLnRvdWNoZXNEaXJlY3Rpb247XG4gIHN3aXBlci5zd2lwZURpcmVjdGlvbiA9IGRpZmYgPiAwID8gJ3ByZXYnIDogJ25leHQnO1xuICBzd2lwZXIudG91Y2hlc0RpcmVjdGlvbiA9IHRvdWNoZXNEaWZmID4gMCA/ICdwcmV2JyA6ICduZXh0JztcbiAgY29uc3QgaXNMb29wID0gc3dpcGVyLnBhcmFtcy5sb29wICYmICFwYXJhbXMuY3NzTW9kZTtcbiAgY29uc3QgYWxsb3dMb29wRml4ID0gc3dpcGVyLnRvdWNoZXNEaXJlY3Rpb24gPT09ICduZXh0JyAmJiBzd2lwZXIuYWxsb3dTbGlkZU5leHQgfHwgc3dpcGVyLnRvdWNoZXNEaXJlY3Rpb24gPT09ICdwcmV2JyAmJiBzd2lwZXIuYWxsb3dTbGlkZVByZXY7XG4gIGlmICghZGF0YS5pc01vdmVkKSB7XG4gICAgaWYgKGlzTG9vcCAmJiBhbGxvd0xvb3BGaXgpIHtcbiAgICAgIHN3aXBlci5sb29wRml4KHtcbiAgICAgICAgZGlyZWN0aW9uOiBzd2lwZXIuc3dpcGVEaXJlY3Rpb25cbiAgICAgIH0pO1xuICAgIH1cbiAgICBkYXRhLnN0YXJ0VHJhbnNsYXRlID0gc3dpcGVyLmdldFRyYW5zbGF0ZSgpO1xuICAgIHN3aXBlci5zZXRUcmFuc2l0aW9uKDApO1xuICAgIGlmIChzd2lwZXIuYW5pbWF0aW5nKSB7XG4gICAgICBjb25zdCBldnQgPSBuZXcgd2luZG93LkN1c3RvbUV2ZW50KCd0cmFuc2l0aW9uZW5kJywge1xuICAgICAgICBidWJibGVzOiB0cnVlLFxuICAgICAgICBjYW5jZWxhYmxlOiB0cnVlLFxuICAgICAgICBkZXRhaWw6IHtcbiAgICAgICAgICBieVN3aXBlclRvdWNoTW92ZTogdHJ1ZVxuICAgICAgICB9XG4gICAgICB9KTtcbiAgICAgIHN3aXBlci53cmFwcGVyRWwuZGlzcGF0Y2hFdmVudChldnQpO1xuICAgIH1cbiAgICBkYXRhLmFsbG93TW9tZW50dW1Cb3VuY2UgPSBmYWxzZTtcbiAgICAvLyBHcmFiIEN1cnNvclxuICAgIGlmIChwYXJhbXMuZ3JhYkN1cnNvciAmJiAoc3dpcGVyLmFsbG93U2xpZGVOZXh0ID09PSB0cnVlIHx8IHN3aXBlci5hbGxvd1NsaWRlUHJldiA9PT0gdHJ1ZSkpIHtcbiAgICAgIHN3aXBlci5zZXRHcmFiQ3Vyc29yKHRydWUpO1xuICAgIH1cbiAgICBzd2lwZXIuZW1pdCgnc2xpZGVyRmlyc3RNb3ZlJywgZSk7XG4gIH1cbiAgbGV0IGxvb3BGaXhlZDtcbiAgbmV3IERhdGUoKS5nZXRUaW1lKCk7XG4gIGlmIChkYXRhLmlzTW92ZWQgJiYgZGF0YS5hbGxvd1RocmVzaG9sZE1vdmUgJiYgcHJldlRvdWNoZXNEaXJlY3Rpb24gIT09IHN3aXBlci50b3VjaGVzRGlyZWN0aW9uICYmIGlzTG9vcCAmJiBhbGxvd0xvb3BGaXggJiYgTWF0aC5hYnMoZGlmZikgPj0gMSkge1xuICAgIE9iamVjdC5hc3NpZ24odG91Y2hlcywge1xuICAgICAgc3RhcnRYOiBwYWdlWCxcbiAgICAgIHN0YXJ0WTogcGFnZVksXG4gICAgICBjdXJyZW50WDogcGFnZVgsXG4gICAgICBjdXJyZW50WTogcGFnZVksXG4gICAgICBzdGFydFRyYW5zbGF0ZTogZGF0YS5jdXJyZW50VHJhbnNsYXRlXG4gICAgfSk7XG4gICAgZGF0YS5sb29wU3dhcFJlc2V0ID0gdHJ1ZTtcbiAgICBkYXRhLnN0YXJ0VHJhbnNsYXRlID0gZGF0YS5jdXJyZW50VHJhbnNsYXRlO1xuICAgIHJldHVybjtcbiAgfVxuICBzd2lwZXIuZW1pdCgnc2xpZGVyTW92ZScsIGUpO1xuICBkYXRhLmlzTW92ZWQgPSB0cnVlO1xuICBkYXRhLmN1cnJlbnRUcmFuc2xhdGUgPSBkaWZmICsgZGF0YS5zdGFydFRyYW5zbGF0ZTtcbiAgbGV0IGRpc2FibGVQYXJlbnRTd2lwZXIgPSB0cnVlO1xuICBsZXQgcmVzaXN0YW5jZVJhdGlvID0gcGFyYW1zLnJlc2lzdGFuY2VSYXRpbztcbiAgaWYgKHBhcmFtcy50b3VjaFJlbGVhc2VPbkVkZ2VzKSB7XG4gICAgcmVzaXN0YW5jZVJhdGlvID0gMDtcbiAgfVxuICBpZiAoZGlmZiA+IDApIHtcbiAgICBpZiAoaXNMb29wICYmIGFsbG93TG9vcEZpeCAmJiAhbG9vcEZpeGVkICYmIGRhdGEuYWxsb3dUaHJlc2hvbGRNb3ZlICYmIGRhdGEuY3VycmVudFRyYW5zbGF0ZSA+IChwYXJhbXMuY2VudGVyZWRTbGlkZXMgPyBzd2lwZXIubWluVHJhbnNsYXRlKCkgLSBzd2lwZXIuc2xpZGVzU2l6ZXNHcmlkW3N3aXBlci5hY3RpdmVJbmRleCArIDFdIC0gKHBhcmFtcy5zbGlkZXNQZXJWaWV3ICE9PSAnYXV0bycgJiYgc3dpcGVyLnNsaWRlcy5sZW5ndGggLSBwYXJhbXMuc2xpZGVzUGVyVmlldyA+PSAyID8gc3dpcGVyLnNsaWRlc1NpemVzR3JpZFtzd2lwZXIuYWN0aXZlSW5kZXggKyAxXSArIHN3aXBlci5wYXJhbXMuc3BhY2VCZXR3ZWVuIDogMCkgLSBzd2lwZXIucGFyYW1zLnNwYWNlQmV0d2VlbiA6IHN3aXBlci5taW5UcmFuc2xhdGUoKSkpIHtcbiAgICAgIHN3aXBlci5sb29wRml4KHtcbiAgICAgICAgZGlyZWN0aW9uOiAncHJldicsXG4gICAgICAgIHNldFRyYW5zbGF0ZTogdHJ1ZSxcbiAgICAgICAgYWN0aXZlU2xpZGVJbmRleDogMFxuICAgICAgfSk7XG4gICAgfVxuICAgIGlmIChkYXRhLmN1cnJlbnRUcmFuc2xhdGUgPiBzd2lwZXIubWluVHJhbnNsYXRlKCkpIHtcbiAgICAgIGRpc2FibGVQYXJlbnRTd2lwZXIgPSBmYWxzZTtcbiAgICAgIGlmIChwYXJhbXMucmVzaXN0YW5jZSkge1xuICAgICAgICBkYXRhLmN1cnJlbnRUcmFuc2xhdGUgPSBzd2lwZXIubWluVHJhbnNsYXRlKCkgLSAxICsgKC1zd2lwZXIubWluVHJhbnNsYXRlKCkgKyBkYXRhLnN0YXJ0VHJhbnNsYXRlICsgZGlmZikgKiogcmVzaXN0YW5jZVJhdGlvO1xuICAgICAgfVxuICAgIH1cbiAgfSBlbHNlIGlmIChkaWZmIDwgMCkge1xuICAgIGlmIChpc0xvb3AgJiYgYWxsb3dMb29wRml4ICYmICFsb29wRml4ZWQgJiYgZGF0YS5hbGxvd1RocmVzaG9sZE1vdmUgJiYgZGF0YS5jdXJyZW50VHJhbnNsYXRlIDwgKHBhcmFtcy5jZW50ZXJlZFNsaWRlcyA/IHN3aXBlci5tYXhUcmFuc2xhdGUoKSArIHN3aXBlci5zbGlkZXNTaXplc0dyaWRbc3dpcGVyLnNsaWRlc1NpemVzR3JpZC5sZW5ndGggLSAxXSArIHN3aXBlci5wYXJhbXMuc3BhY2VCZXR3ZWVuICsgKHBhcmFtcy5zbGlkZXNQZXJWaWV3ICE9PSAnYXV0bycgJiYgc3dpcGVyLnNsaWRlcy5sZW5ndGggLSBwYXJhbXMuc2xpZGVzUGVyVmlldyA+PSAyID8gc3dpcGVyLnNsaWRlc1NpemVzR3JpZFtzd2lwZXIuc2xpZGVzU2l6ZXNHcmlkLmxlbmd0aCAtIDFdICsgc3dpcGVyLnBhcmFtcy5zcGFjZUJldHdlZW4gOiAwKSA6IHN3aXBlci5tYXhUcmFuc2xhdGUoKSkpIHtcbiAgICAgIHN3aXBlci5sb29wRml4KHtcbiAgICAgICAgZGlyZWN0aW9uOiAnbmV4dCcsXG4gICAgICAgIHNldFRyYW5zbGF0ZTogdHJ1ZSxcbiAgICAgICAgYWN0aXZlU2xpZGVJbmRleDogc3dpcGVyLnNsaWRlcy5sZW5ndGggLSAocGFyYW1zLnNsaWRlc1BlclZpZXcgPT09ICdhdXRvJyA/IHN3aXBlci5zbGlkZXNQZXJWaWV3RHluYW1pYygpIDogTWF0aC5jZWlsKHBhcnNlRmxvYXQocGFyYW1zLnNsaWRlc1BlclZpZXcsIDEwKSkpXG4gICAgICB9KTtcbiAgICB9XG4gICAgaWYgKGRhdGEuY3VycmVudFRyYW5zbGF0ZSA8IHN3aXBlci5tYXhUcmFuc2xhdGUoKSkge1xuICAgICAgZGlzYWJsZVBhcmVudFN3aXBlciA9IGZhbHNlO1xuICAgICAgaWYgKHBhcmFtcy5yZXNpc3RhbmNlKSB7XG4gICAgICAgIGRhdGEuY3VycmVudFRyYW5zbGF0ZSA9IHN3aXBlci5tYXhUcmFuc2xhdGUoKSArIDEgLSAoc3dpcGVyLm1heFRyYW5zbGF0ZSgpIC0gZGF0YS5zdGFydFRyYW5zbGF0ZSAtIGRpZmYpICoqIHJlc2lzdGFuY2VSYXRpbztcbiAgICAgIH1cbiAgICB9XG4gIH1cbiAgaWYgKGRpc2FibGVQYXJlbnRTd2lwZXIpIHtcbiAgICBlLnByZXZlbnRlZEJ5TmVzdGVkU3dpcGVyID0gdHJ1ZTtcbiAgfVxuXG4gIC8vIERpcmVjdGlvbnMgbG9ja3NcbiAgaWYgKCFzd2lwZXIuYWxsb3dTbGlkZU5leHQgJiYgc3dpcGVyLnN3aXBlRGlyZWN0aW9uID09PSAnbmV4dCcgJiYgZGF0YS5jdXJyZW50VHJhbnNsYXRlIDwgZGF0YS5zdGFydFRyYW5zbGF0ZSkge1xuICAgIGRhdGEuY3VycmVudFRyYW5zbGF0ZSA9IGRhdGEuc3RhcnRUcmFuc2xhdGU7XG4gIH1cbiAgaWYgKCFzd2lwZXIuYWxsb3dTbGlkZVByZXYgJiYgc3dpcGVyLnN3aXBlRGlyZWN0aW9uID09PSAncHJldicgJiYgZGF0YS5jdXJyZW50VHJhbnNsYXRlID4gZGF0YS5zdGFydFRyYW5zbGF0ZSkge1xuICAgIGRhdGEuY3VycmVudFRyYW5zbGF0ZSA9IGRhdGEuc3RhcnRUcmFuc2xhdGU7XG4gIH1cbiAgaWYgKCFzd2lwZXIuYWxsb3dTbGlkZVByZXYgJiYgIXN3aXBlci5hbGxvd1NsaWRlTmV4dCkge1xuICAgIGRhdGEuY3VycmVudFRyYW5zbGF0ZSA9IGRhdGEuc3RhcnRUcmFuc2xhdGU7XG4gIH1cblxuICAvLyBUaHJlc2hvbGRcbiAgaWYgKHBhcmFtcy50aHJlc2hvbGQgPiAwKSB7XG4gICAgaWYgKE1hdGguYWJzKGRpZmYpID4gcGFyYW1zLnRocmVzaG9sZCB8fCBkYXRhLmFsbG93VGhyZXNob2xkTW92ZSkge1xuICAgICAgaWYgKCFkYXRhLmFsbG93VGhyZXNob2xkTW92ZSkge1xuICAgICAgICBkYXRhLmFsbG93VGhyZXNob2xkTW92ZSA9IHRydWU7XG4gICAgICAgIHRvdWNoZXMuc3RhcnRYID0gdG91Y2hlcy5jdXJyZW50WDtcbiAgICAgICAgdG91Y2hlcy5zdGFydFkgPSB0b3VjaGVzLmN1cnJlbnRZO1xuICAgICAgICBkYXRhLmN1cnJlbnRUcmFuc2xhdGUgPSBkYXRhLnN0YXJ0VHJhbnNsYXRlO1xuICAgICAgICB0b3VjaGVzLmRpZmYgPSBzd2lwZXIuaXNIb3Jpem9udGFsKCkgPyB0b3VjaGVzLmN1cnJlbnRYIC0gdG91Y2hlcy5zdGFydFggOiB0b3VjaGVzLmN1cnJlbnRZIC0gdG91Y2hlcy5zdGFydFk7XG4gICAgICAgIHJldHVybjtcbiAgICAgIH1cbiAgICB9IGVsc2Uge1xuICAgICAgZGF0YS5jdXJyZW50VHJhbnNsYXRlID0gZGF0YS5zdGFydFRyYW5zbGF0ZTtcbiAgICAgIHJldHVybjtcbiAgICB9XG4gIH1cbiAgaWYgKCFwYXJhbXMuZm9sbG93RmluZ2VyIHx8IHBhcmFtcy5jc3NNb2RlKSByZXR1cm47XG5cbiAgLy8gVXBkYXRlIGFjdGl2ZSBpbmRleCBpbiBmcmVlIG1vZGVcbiAgaWYgKHBhcmFtcy5mcmVlTW9kZSAmJiBwYXJhbXMuZnJlZU1vZGUuZW5hYmxlZCAmJiBzd2lwZXIuZnJlZU1vZGUgfHwgcGFyYW1zLndhdGNoU2xpZGVzUHJvZ3Jlc3MpIHtcbiAgICBzd2lwZXIudXBkYXRlQWN0aXZlSW5kZXgoKTtcbiAgICBzd2lwZXIudXBkYXRlU2xpZGVzQ2xhc3NlcygpO1xuICB9XG4gIGlmIChwYXJhbXMuZnJlZU1vZGUgJiYgcGFyYW1zLmZyZWVNb2RlLmVuYWJsZWQgJiYgc3dpcGVyLmZyZWVNb2RlKSB7XG4gICAgc3dpcGVyLmZyZWVNb2RlLm9uVG91Y2hNb3ZlKCk7XG4gIH1cbiAgLy8gVXBkYXRlIHByb2dyZXNzXG4gIHN3aXBlci51cGRhdGVQcm9ncmVzcyhkYXRhLmN1cnJlbnRUcmFuc2xhdGUpO1xuICAvLyBVcGRhdGUgdHJhbnNsYXRlXG4gIHN3aXBlci5zZXRUcmFuc2xhdGUoZGF0YS5jdXJyZW50VHJhbnNsYXRlKTtcbn1cblxuZnVuY3Rpb24gb25Ub3VjaEVuZChldmVudCkge1xuICBjb25zdCBzd2lwZXIgPSB0aGlzO1xuICBjb25zdCBkYXRhID0gc3dpcGVyLnRvdWNoRXZlbnRzRGF0YTtcbiAgbGV0IGUgPSBldmVudDtcbiAgaWYgKGUub3JpZ2luYWxFdmVudCkgZSA9IGUub3JpZ2luYWxFdmVudDtcbiAgbGV0IHRhcmdldFRvdWNoO1xuICBjb25zdCBpc1RvdWNoRXZlbnQgPSBlLnR5cGUgPT09ICd0b3VjaGVuZCcgfHwgZS50eXBlID09PSAndG91Y2hjYW5jZWwnO1xuICBpZiAoIWlzVG91Y2hFdmVudCkge1xuICAgIGlmIChkYXRhLnRvdWNoSWQgIT09IG51bGwpIHJldHVybjsgLy8gcmV0dXJuIGZyb20gcG9pbnRlciBpZiB3ZSB1c2UgdG91Y2hcbiAgICBpZiAoZS5wb2ludGVySWQgIT09IGRhdGEucG9pbnRlcklkKSByZXR1cm47XG4gICAgdGFyZ2V0VG91Y2ggPSBlO1xuICB9IGVsc2Uge1xuICAgIHRhcmdldFRvdWNoID0gWy4uLmUuY2hhbmdlZFRvdWNoZXNdLmZpbHRlcih0ID0+IHQuaWRlbnRpZmllciA9PT0gZGF0YS50b3VjaElkKVswXTtcbiAgICBpZiAoIXRhcmdldFRvdWNoIHx8IHRhcmdldFRvdWNoLmlkZW50aWZpZXIgIT09IGRhdGEudG91Y2hJZCkgcmV0dXJuO1xuICB9XG4gIGlmIChbJ3BvaW50ZXJjYW5jZWwnLCAncG9pbnRlcm91dCcsICdwb2ludGVybGVhdmUnLCAnY29udGV4dG1lbnUnXS5pbmNsdWRlcyhlLnR5cGUpKSB7XG4gICAgY29uc3QgcHJvY2VlZCA9IFsncG9pbnRlcmNhbmNlbCcsICdjb250ZXh0bWVudSddLmluY2x1ZGVzKGUudHlwZSkgJiYgKHN3aXBlci5icm93c2VyLmlzU2FmYXJpIHx8IHN3aXBlci5icm93c2VyLmlzV2ViVmlldyk7XG4gICAgaWYgKCFwcm9jZWVkKSB7XG4gICAgICByZXR1cm47XG4gICAgfVxuICB9XG4gIGRhdGEucG9pbnRlcklkID0gbnVsbDtcbiAgZGF0YS50b3VjaElkID0gbnVsbDtcbiAgY29uc3Qge1xuICAgIHBhcmFtcyxcbiAgICB0b3VjaGVzLFxuICAgIHJ0bFRyYW5zbGF0ZTogcnRsLFxuICAgIHNsaWRlc0dyaWQsXG4gICAgZW5hYmxlZFxuICB9ID0gc3dpcGVyO1xuICBpZiAoIWVuYWJsZWQpIHJldHVybjtcbiAgaWYgKCFwYXJhbXMuc2ltdWxhdGVUb3VjaCAmJiBlLnBvaW50ZXJUeXBlID09PSAnbW91c2UnKSByZXR1cm47XG4gIGlmIChkYXRhLmFsbG93VG91Y2hDYWxsYmFja3MpIHtcbiAgICBzd2lwZXIuZW1pdCgndG91Y2hFbmQnLCBlKTtcbiAgfVxuICBkYXRhLmFsbG93VG91Y2hDYWxsYmFja3MgPSBmYWxzZTtcbiAgaWYgKCFkYXRhLmlzVG91Y2hlZCkge1xuICAgIGlmIChkYXRhLmlzTW92ZWQgJiYgcGFyYW1zLmdyYWJDdXJzb3IpIHtcbiAgICAgIHN3aXBlci5zZXRHcmFiQ3Vyc29yKGZhbHNlKTtcbiAgICB9XG4gICAgZGF0YS5pc01vdmVkID0gZmFsc2U7XG4gICAgZGF0YS5zdGFydE1vdmluZyA9IGZhbHNlO1xuICAgIHJldHVybjtcbiAgfVxuXG4gIC8vIFJldHVybiBHcmFiIEN1cnNvclxuICBpZiAocGFyYW1zLmdyYWJDdXJzb3IgJiYgZGF0YS5pc01vdmVkICYmIGRhdGEuaXNUb3VjaGVkICYmIChzd2lwZXIuYWxsb3dTbGlkZU5leHQgPT09IHRydWUgfHwgc3dpcGVyLmFsbG93U2xpZGVQcmV2ID09PSB0cnVlKSkge1xuICAgIHN3aXBlci5zZXRHcmFiQ3Vyc29yKGZhbHNlKTtcbiAgfVxuXG4gIC8vIFRpbWUgZGlmZlxuICBjb25zdCB0b3VjaEVuZFRpbWUgPSBub3coKTtcbiAgY29uc3QgdGltZURpZmYgPSB0b3VjaEVuZFRpbWUgLSBkYXRhLnRvdWNoU3RhcnRUaW1lO1xuXG4gIC8vIFRhcCwgZG91YmxlVGFwLCBDbGlja1xuICBpZiAoc3dpcGVyLmFsbG93Q2xpY2spIHtcbiAgICBjb25zdCBwYXRoVHJlZSA9IGUucGF0aCB8fCBlLmNvbXBvc2VkUGF0aCAmJiBlLmNvbXBvc2VkUGF0aCgpO1xuICAgIHN3aXBlci51cGRhdGVDbGlja2VkU2xpZGUocGF0aFRyZWUgJiYgcGF0aFRyZWVbMF0gfHwgZS50YXJnZXQsIHBhdGhUcmVlKTtcbiAgICBzd2lwZXIuZW1pdCgndGFwIGNsaWNrJywgZSk7XG4gICAgaWYgKHRpbWVEaWZmIDwgMzAwICYmIHRvdWNoRW5kVGltZSAtIGRhdGEubGFzdENsaWNrVGltZSA8IDMwMCkge1xuICAgICAgc3dpcGVyLmVtaXQoJ2RvdWJsZVRhcCBkb3VibGVDbGljaycsIGUpO1xuICAgIH1cbiAgfVxuICBkYXRhLmxhc3RDbGlja1RpbWUgPSBub3coKTtcbiAgbmV4dFRpY2soKCkgPT4ge1xuICAgIGlmICghc3dpcGVyLmRlc3Ryb3llZCkgc3dpcGVyLmFsbG93Q2xpY2sgPSB0cnVlO1xuICB9KTtcbiAgaWYgKCFkYXRhLmlzVG91Y2hlZCB8fCAhZGF0YS5pc01vdmVkIHx8ICFzd2lwZXIuc3dpcGVEaXJlY3Rpb24gfHwgdG91Y2hlcy5kaWZmID09PSAwICYmICFkYXRhLmxvb3BTd2FwUmVzZXQgfHwgZGF0YS5jdXJyZW50VHJhbnNsYXRlID09PSBkYXRhLnN0YXJ0VHJhbnNsYXRlICYmICFkYXRhLmxvb3BTd2FwUmVzZXQpIHtcbiAgICBkYXRhLmlzVG91Y2hlZCA9IGZhbHNlO1xuICAgIGRhdGEuaXNNb3ZlZCA9IGZhbHNlO1xuICAgIGRhdGEuc3RhcnRNb3ZpbmcgPSBmYWxzZTtcbiAgICByZXR1cm47XG4gIH1cbiAgZGF0YS5pc1RvdWNoZWQgPSBmYWxzZTtcbiAgZGF0YS5pc01vdmVkID0gZmFsc2U7XG4gIGRhdGEuc3RhcnRNb3ZpbmcgPSBmYWxzZTtcbiAgbGV0IGN1cnJlbnRQb3M7XG4gIGlmIChwYXJhbXMuZm9sbG93RmluZ2VyKSB7XG4gICAgY3VycmVudFBvcyA9IHJ0bCA/IHN3aXBlci50cmFuc2xhdGUgOiAtc3dpcGVyLnRyYW5zbGF0ZTtcbiAgfSBlbHNlIHtcbiAgICBjdXJyZW50UG9zID0gLWRhdGEuY3VycmVudFRyYW5zbGF0ZTtcbiAgfVxuICBpZiAocGFyYW1zLmNzc01vZGUpIHtcbiAgICByZXR1cm47XG4gIH1cbiAgaWYgKHBhcmFtcy5mcmVlTW9kZSAmJiBwYXJhbXMuZnJlZU1vZGUuZW5hYmxlZCkge1xuICAgIHN3aXBlci5mcmVlTW9kZS5vblRvdWNoRW5kKHtcbiAgICAgIGN1cnJlbnRQb3NcbiAgICB9KTtcbiAgICByZXR1cm47XG4gIH1cblxuICAvLyBGaW5kIGN1cnJlbnQgc2xpZGVcbiAgY29uc3Qgc3dpcGVUb0xhc3QgPSBjdXJyZW50UG9zID49IC1zd2lwZXIubWF4VHJhbnNsYXRlKCkgJiYgIXN3aXBlci5wYXJhbXMubG9vcDtcbiAgbGV0IHN0b3BJbmRleCA9IDA7XG4gIGxldCBncm91cFNpemUgPSBzd2lwZXIuc2xpZGVzU2l6ZXNHcmlkWzBdO1xuICBmb3IgKGxldCBpID0gMDsgaSA8IHNsaWRlc0dyaWQubGVuZ3RoOyBpICs9IGkgPCBwYXJhbXMuc2xpZGVzUGVyR3JvdXBTa2lwID8gMSA6IHBhcmFtcy5zbGlkZXNQZXJHcm91cCkge1xuICAgIGNvbnN0IGluY3JlbWVudCA9IGkgPCBwYXJhbXMuc2xpZGVzUGVyR3JvdXBTa2lwIC0gMSA/IDEgOiBwYXJhbXMuc2xpZGVzUGVyR3JvdXA7XG4gICAgaWYgKHR5cGVvZiBzbGlkZXNHcmlkW2kgKyBpbmNyZW1lbnRdICE9PSAndW5kZWZpbmVkJykge1xuICAgICAgaWYgKHN3aXBlVG9MYXN0IHx8IGN1cnJlbnRQb3MgPj0gc2xpZGVzR3JpZFtpXSAmJiBjdXJyZW50UG9zIDwgc2xpZGVzR3JpZFtpICsgaW5jcmVtZW50XSkge1xuICAgICAgICBzdG9wSW5kZXggPSBpO1xuICAgICAgICBncm91cFNpemUgPSBzbGlkZXNHcmlkW2kgKyBpbmNyZW1lbnRdIC0gc2xpZGVzR3JpZFtpXTtcbiAgICAgIH1cbiAgICB9IGVsc2UgaWYgKHN3aXBlVG9MYXN0IHx8IGN1cnJlbnRQb3MgPj0gc2xpZGVzR3JpZFtpXSkge1xuICAgICAgc3RvcEluZGV4ID0gaTtcbiAgICAgIGdyb3VwU2l6ZSA9IHNsaWRlc0dyaWRbc2xpZGVzR3JpZC5sZW5ndGggLSAxXSAtIHNsaWRlc0dyaWRbc2xpZGVzR3JpZC5sZW5ndGggLSAyXTtcbiAgICB9XG4gIH1cbiAgbGV0IHJld2luZEZpcnN0SW5kZXggPSBudWxsO1xuICBsZXQgcmV3aW5kTGFzdEluZGV4ID0gbnVsbDtcbiAgaWYgKHBhcmFtcy5yZXdpbmQpIHtcbiAgICBpZiAoc3dpcGVyLmlzQmVnaW5uaW5nKSB7XG4gICAgICByZXdpbmRMYXN0SW5kZXggPSBwYXJhbXMudmlydHVhbCAmJiBwYXJhbXMudmlydHVhbC5lbmFibGVkICYmIHN3aXBlci52aXJ0dWFsID8gc3dpcGVyLnZpcnR1YWwuc2xpZGVzLmxlbmd0aCAtIDEgOiBzd2lwZXIuc2xpZGVzLmxlbmd0aCAtIDE7XG4gICAgfSBlbHNlIGlmIChzd2lwZXIuaXNFbmQpIHtcbiAgICAgIHJld2luZEZpcnN0SW5kZXggPSAwO1xuICAgIH1cbiAgfVxuICAvLyBGaW5kIGN1cnJlbnQgc2xpZGUgc2l6ZVxuICBjb25zdCByYXRpbyA9IChjdXJyZW50UG9zIC0gc2xpZGVzR3JpZFtzdG9wSW5kZXhdKSAvIGdyb3VwU2l6ZTtcbiAgY29uc3QgaW5jcmVtZW50ID0gc3RvcEluZGV4IDwgcGFyYW1zLnNsaWRlc1Blckdyb3VwU2tpcCAtIDEgPyAxIDogcGFyYW1zLnNsaWRlc1Blckdyb3VwO1xuICBpZiAodGltZURpZmYgPiBwYXJhbXMubG9uZ1N3aXBlc01zKSB7XG4gICAgLy8gTG9uZyB0b3VjaGVzXG4gICAgaWYgKCFwYXJhbXMubG9uZ1N3aXBlcykge1xuICAgICAgc3dpcGVyLnNsaWRlVG8oc3dpcGVyLmFjdGl2ZUluZGV4KTtcbiAgICAgIHJldHVybjtcbiAgICB9XG4gICAgaWYgKHN3aXBlci5zd2lwZURpcmVjdGlvbiA9PT0gJ25leHQnKSB7XG4gICAgICBpZiAocmF0aW8gPj0gcGFyYW1zLmxvbmdTd2lwZXNSYXRpbykgc3dpcGVyLnNsaWRlVG8ocGFyYW1zLnJld2luZCAmJiBzd2lwZXIuaXNFbmQgPyByZXdpbmRGaXJzdEluZGV4IDogc3RvcEluZGV4ICsgaW5jcmVtZW50KTtlbHNlIHN3aXBlci5zbGlkZVRvKHN0b3BJbmRleCk7XG4gICAgfVxuICAgIGlmIChzd2lwZXIuc3dpcGVEaXJlY3Rpb24gPT09ICdwcmV2Jykge1xuICAgICAgaWYgKHJhdGlvID4gMSAtIHBhcmFtcy5sb25nU3dpcGVzUmF0aW8pIHtcbiAgICAgICAgc3dpcGVyLnNsaWRlVG8oc3RvcEluZGV4ICsgaW5jcmVtZW50KTtcbiAgICAgIH0gZWxzZSBpZiAocmV3aW5kTGFzdEluZGV4ICE9PSBudWxsICYmIHJhdGlvIDwgMCAmJiBNYXRoLmFicyhyYXRpbykgPiBwYXJhbXMubG9uZ1N3aXBlc1JhdGlvKSB7XG4gICAgICAgIHN3aXBlci5zbGlkZVRvKHJld2luZExhc3RJbmRleCk7XG4gICAgICB9IGVsc2Uge1xuICAgICAgICBzd2lwZXIuc2xpZGVUbyhzdG9wSW5kZXgpO1xuICAgICAgfVxuICAgIH1cbiAgfSBlbHNlIHtcbiAgICAvLyBTaG9ydCBzd2lwZXNcbiAgICBpZiAoIXBhcmFtcy5zaG9ydFN3aXBlcykge1xuICAgICAgc3dpcGVyLnNsaWRlVG8oc3dpcGVyLmFjdGl2ZUluZGV4KTtcbiAgICAgIHJldHVybjtcbiAgICB9XG4gICAgY29uc3QgaXNOYXZCdXR0b25UYXJnZXQgPSBzd2lwZXIubmF2aWdhdGlvbiAmJiAoZS50YXJnZXQgPT09IHN3aXBlci5uYXZpZ2F0aW9uLm5leHRFbCB8fCBlLnRhcmdldCA9PT0gc3dpcGVyLm5hdmlnYXRpb24ucHJldkVsKTtcbiAgICBpZiAoIWlzTmF2QnV0dG9uVGFyZ2V0KSB7XG4gICAgICBpZiAoc3dpcGVyLnN3aXBlRGlyZWN0aW9uID09PSAnbmV4dCcpIHtcbiAgICAgICAgc3dpcGVyLnNsaWRlVG8ocmV3aW5kRmlyc3RJbmRleCAhPT0gbnVsbCA/IHJld2luZEZpcnN0SW5kZXggOiBzdG9wSW5kZXggKyBpbmNyZW1lbnQpO1xuICAgICAgfVxuICAgICAgaWYgKHN3aXBlci5zd2lwZURpcmVjdGlvbiA9PT0gJ3ByZXYnKSB7XG4gICAgICAgIHN3aXBlci5zbGlkZVRvKHJld2luZExhc3RJbmRleCAhPT0gbnVsbCA/IHJld2luZExhc3RJbmRleCA6IHN0b3BJbmRleCk7XG4gICAgICB9XG4gICAgfSBlbHNlIGlmIChlLnRhcmdldCA9PT0gc3dpcGVyLm5hdmlnYXRpb24ubmV4dEVsKSB7XG4gICAgICBzd2lwZXIuc2xpZGVUbyhzdG9wSW5kZXggKyBpbmNyZW1lbnQpO1xuICAgIH0gZWxzZSB7XG4gICAgICBzd2lwZXIuc2xpZGVUbyhzdG9wSW5kZXgpO1xuICAgIH1cbiAgfVxufVxuXG5mdW5jdGlvbiBvblJlc2l6ZSgpIHtcbiAgY29uc3Qgc3dpcGVyID0gdGhpcztcbiAgY29uc3Qge1xuICAgIHBhcmFtcyxcbiAgICBlbFxuICB9ID0gc3dpcGVyO1xuICBpZiAoZWwgJiYgZWwub2Zmc2V0V2lkdGggPT09IDApIHJldHVybjtcblxuICAvLyBCcmVha3BvaW50c1xuICBpZiAocGFyYW1zLmJyZWFrcG9pbnRzKSB7XG4gICAgc3dpcGVyLnNldEJyZWFrcG9pbnQoKTtcbiAgfVxuXG4gIC8vIFNhdmUgbG9ja3NcbiAgY29uc3Qge1xuICAgIGFsbG93U2xpZGVOZXh0LFxuICAgIGFsbG93U2xpZGVQcmV2LFxuICAgIHNuYXBHcmlkXG4gIH0gPSBzd2lwZXI7XG4gIGNvbnN0IGlzVmlydHVhbCA9IHN3aXBlci52aXJ0dWFsICYmIHN3aXBlci5wYXJhbXMudmlydHVhbC5lbmFibGVkO1xuXG4gIC8vIERpc2FibGUgbG9ja3Mgb24gcmVzaXplXG4gIHN3aXBlci5hbGxvd1NsaWRlTmV4dCA9IHRydWU7XG4gIHN3aXBlci5hbGxvd1NsaWRlUHJldiA9IHRydWU7XG4gIHN3aXBlci51cGRhdGVTaXplKCk7XG4gIHN3aXBlci51cGRhdGVTbGlkZXMoKTtcbiAgc3dpcGVyLnVwZGF0ZVNsaWRlc0NsYXNzZXMoKTtcbiAgY29uc3QgaXNWaXJ0dWFsTG9vcCA9IGlzVmlydHVhbCAmJiBwYXJhbXMubG9vcDtcbiAgaWYgKChwYXJhbXMuc2xpZGVzUGVyVmlldyA9PT0gJ2F1dG8nIHx8IHBhcmFtcy5zbGlkZXNQZXJWaWV3ID4gMSkgJiYgc3dpcGVyLmlzRW5kICYmICFzd2lwZXIuaXNCZWdpbm5pbmcgJiYgIXN3aXBlci5wYXJhbXMuY2VudGVyZWRTbGlkZXMgJiYgIWlzVmlydHVhbExvb3ApIHtcbiAgICBzd2lwZXIuc2xpZGVUbyhzd2lwZXIuc2xpZGVzLmxlbmd0aCAtIDEsIDAsIGZhbHNlLCB0cnVlKTtcbiAgfSBlbHNlIHtcbiAgICBpZiAoc3dpcGVyLnBhcmFtcy5sb29wICYmICFpc1ZpcnR1YWwpIHtcbiAgICAgIHN3aXBlci5zbGlkZVRvTG9vcChzd2lwZXIucmVhbEluZGV4LCAwLCBmYWxzZSwgdHJ1ZSk7XG4gICAgfSBlbHNlIHtcbiAgICAgIHN3aXBlci5zbGlkZVRvKHN3aXBlci5hY3RpdmVJbmRleCwgMCwgZmFsc2UsIHRydWUpO1xuICAgIH1cbiAgfVxuICBpZiAoc3dpcGVyLmF1dG9wbGF5ICYmIHN3aXBlci5hdXRvcGxheS5ydW5uaW5nICYmIHN3aXBlci5hdXRvcGxheS5wYXVzZWQpIHtcbiAgICBjbGVhclRpbWVvdXQoc3dpcGVyLmF1dG9wbGF5LnJlc2l6ZVRpbWVvdXQpO1xuICAgIHN3aXBlci5hdXRvcGxheS5yZXNpemVUaW1lb3V0ID0gc2V0VGltZW91dCgoKSA9PiB7XG4gICAgICBpZiAoc3dpcGVyLmF1dG9wbGF5ICYmIHN3aXBlci5hdXRvcGxheS5ydW5uaW5nICYmIHN3aXBlci5hdXRvcGxheS5wYXVzZWQpIHtcbiAgICAgICAgc3dpcGVyLmF1dG9wbGF5LnJlc3VtZSgpO1xuICAgICAgfVxuICAgIH0sIDUwMCk7XG4gIH1cbiAgLy8gUmV0dXJuIGxvY2tzIGFmdGVyIHJlc2l6ZVxuICBzd2lwZXIuYWxsb3dTbGlkZVByZXYgPSBhbGxvd1NsaWRlUHJldjtcbiAgc3dpcGVyLmFsbG93U2xpZGVOZXh0ID0gYWxsb3dTbGlkZU5leHQ7XG4gIGlmIChzd2lwZXIucGFyYW1zLndhdGNoT3ZlcmZsb3cgJiYgc25hcEdyaWQgIT09IHN3aXBlci5zbmFwR3JpZCkge1xuICAgIHN3aXBlci5jaGVja092ZXJmbG93KCk7XG4gIH1cbn1cblxuZnVuY3Rpb24gb25DbGljayhlKSB7XG4gIGNvbnN0IHN3aXBlciA9IHRoaXM7XG4gIGlmICghc3dpcGVyLmVuYWJsZWQpIHJldHVybjtcbiAgaWYgKCFzd2lwZXIuYWxsb3dDbGljaykge1xuICAgIGlmIChzd2lwZXIucGFyYW1zLnByZXZlbnRDbGlja3MpIGUucHJldmVudERlZmF1bHQoKTtcbiAgICBpZiAoc3dpcGVyLnBhcmFtcy5wcmV2ZW50Q2xpY2tzUHJvcGFnYXRpb24gJiYgc3dpcGVyLmFuaW1hdGluZykge1xuICAgICAgZS5zdG9wUHJvcGFnYXRpb24oKTtcbiAgICAgIGUuc3RvcEltbWVkaWF0ZVByb3BhZ2F0aW9uKCk7XG4gICAgfVxuICB9XG59XG5cbmZ1bmN0aW9uIG9uU2Nyb2xsKCkge1xuICBjb25zdCBzd2lwZXIgPSB0aGlzO1xuICBjb25zdCB7XG4gICAgd3JhcHBlckVsLFxuICAgIHJ0bFRyYW5zbGF0ZSxcbiAgICBlbmFibGVkXG4gIH0gPSBzd2lwZXI7XG4gIGlmICghZW5hYmxlZCkgcmV0dXJuO1xuICBzd2lwZXIucHJldmlvdXNUcmFuc2xhdGUgPSBzd2lwZXIudHJhbnNsYXRlO1xuICBpZiAoc3dpcGVyLmlzSG9yaXpvbnRhbCgpKSB7XG4gICAgc3dpcGVyLnRyYW5zbGF0ZSA9IC13cmFwcGVyRWwuc2Nyb2xsTGVmdDtcbiAgfSBlbHNlIHtcbiAgICBzd2lwZXIudHJhbnNsYXRlID0gLXdyYXBwZXJFbC5zY3JvbGxUb3A7XG4gIH1cbiAgLy8gZXNsaW50LWRpc2FibGUtbmV4dC1saW5lXG4gIGlmIChzd2lwZXIudHJhbnNsYXRlID09PSAwKSBzd2lwZXIudHJhbnNsYXRlID0gMDtcbiAgc3dpcGVyLnVwZGF0ZUFjdGl2ZUluZGV4KCk7XG4gIHN3aXBlci51cGRhdGVTbGlkZXNDbGFzc2VzKCk7XG4gIGxldCBuZXdQcm9ncmVzcztcbiAgY29uc3QgdHJhbnNsYXRlc0RpZmYgPSBzd2lwZXIubWF4VHJhbnNsYXRlKCkgLSBzd2lwZXIubWluVHJhbnNsYXRlKCk7XG4gIGlmICh0cmFuc2xhdGVzRGlmZiA9PT0gMCkge1xuICAgIG5ld1Byb2dyZXNzID0gMDtcbiAgfSBlbHNlIHtcbiAgICBuZXdQcm9ncmVzcyA9IChzd2lwZXIudHJhbnNsYXRlIC0gc3dpcGVyLm1pblRyYW5zbGF0ZSgpKSAvIHRyYW5zbGF0ZXNEaWZmO1xuICB9XG4gIGlmIChuZXdQcm9ncmVzcyAhPT0gc3dpcGVyLnByb2dyZXNzKSB7XG4gICAgc3dpcGVyLnVwZGF0ZVByb2dyZXNzKHJ0bFRyYW5zbGF0ZSA/IC1zd2lwZXIudHJhbnNsYXRlIDogc3dpcGVyLnRyYW5zbGF0ZSk7XG4gIH1cbiAgc3dpcGVyLmVtaXQoJ3NldFRyYW5zbGF0ZScsIHN3aXBlci50cmFuc2xhdGUsIGZhbHNlKTtcbn1cblxuZnVuY3Rpb24gb25Mb2FkKGUpIHtcbiAgY29uc3Qgc3dpcGVyID0gdGhpcztcbiAgcHJvY2Vzc0xhenlQcmVsb2FkZXIoc3dpcGVyLCBlLnRhcmdldCk7XG4gIGlmIChzd2lwZXIucGFyYW1zLmNzc01vZGUgfHwgc3dpcGVyLnBhcmFtcy5zbGlkZXNQZXJWaWV3ICE9PSAnYXV0bycgJiYgIXN3aXBlci5wYXJhbXMuYXV0b0hlaWdodCkge1xuICAgIHJldHVybjtcbiAgfVxuICBzd2lwZXIudXBkYXRlKCk7XG59XG5cbmZ1bmN0aW9uIG9uRG9jdW1lbnRUb3VjaFN0YXJ0KCkge1xuICBjb25zdCBzd2lwZXIgPSB0aGlzO1xuICBpZiAoc3dpcGVyLmRvY3VtZW50VG91Y2hIYW5kbGVyUHJvY2VlZGVkKSByZXR1cm47XG4gIHN3aXBlci5kb2N1bWVudFRvdWNoSGFuZGxlclByb2NlZWRlZCA9IHRydWU7XG4gIGlmIChzd2lwZXIucGFyYW1zLnRvdWNoUmVsZWFzZU9uRWRnZXMpIHtcbiAgICBzd2lwZXIuZWwuc3R5bGUudG91Y2hBY3Rpb24gPSAnYXV0byc7XG4gIH1cbn1cblxuY29uc3QgZXZlbnRzID0gKHN3aXBlciwgbWV0aG9kKSA9PiB7XG4gIGNvbnN0IGRvY3VtZW50ID0gZ2V0RG9jdW1lbnQoKTtcbiAgY29uc3Qge1xuICAgIHBhcmFtcyxcbiAgICBlbCxcbiAgICB3cmFwcGVyRWwsXG4gICAgZGV2aWNlXG4gIH0gPSBzd2lwZXI7XG4gIGNvbnN0IGNhcHR1cmUgPSAhIXBhcmFtcy5uZXN0ZWQ7XG4gIGNvbnN0IGRvbU1ldGhvZCA9IG1ldGhvZCA9PT0gJ29uJyA/ICdhZGRFdmVudExpc3RlbmVyJyA6ICdyZW1vdmVFdmVudExpc3RlbmVyJztcbiAgY29uc3Qgc3dpcGVyTWV0aG9kID0gbWV0aG9kO1xuICBpZiAoIWVsIHx8IHR5cGVvZiBlbCA9PT0gJ3N0cmluZycpIHJldHVybjtcblxuICAvLyBUb3VjaCBFdmVudHNcbiAgZG9jdW1lbnRbZG9tTWV0aG9kXSgndG91Y2hzdGFydCcsIHN3aXBlci5vbkRvY3VtZW50VG91Y2hTdGFydCwge1xuICAgIHBhc3NpdmU6IGZhbHNlLFxuICAgIGNhcHR1cmVcbiAgfSk7XG4gIGVsW2RvbU1ldGhvZF0oJ3RvdWNoc3RhcnQnLCBzd2lwZXIub25Ub3VjaFN0YXJ0LCB7XG4gICAgcGFzc2l2ZTogZmFsc2VcbiAgfSk7XG4gIGVsW2RvbU1ldGhvZF0oJ3BvaW50ZXJkb3duJywgc3dpcGVyLm9uVG91Y2hTdGFydCwge1xuICAgIHBhc3NpdmU6IGZhbHNlXG4gIH0pO1xuICBkb2N1bWVudFtkb21NZXRob2RdKCd0b3VjaG1vdmUnLCBzd2lwZXIub25Ub3VjaE1vdmUsIHtcbiAgICBwYXNzaXZlOiBmYWxzZSxcbiAgICBjYXB0dXJlXG4gIH0pO1xuICBkb2N1bWVudFtkb21NZXRob2RdKCdwb2ludGVybW92ZScsIHN3aXBlci5vblRvdWNoTW92ZSwge1xuICAgIHBhc3NpdmU6IGZhbHNlLFxuICAgIGNhcHR1cmVcbiAgfSk7XG4gIGRvY3VtZW50W2RvbU1ldGhvZF0oJ3RvdWNoZW5kJywgc3dpcGVyLm9uVG91Y2hFbmQsIHtcbiAgICBwYXNzaXZlOiB0cnVlXG4gIH0pO1xuICBkb2N1bWVudFtkb21NZXRob2RdKCdwb2ludGVydXAnLCBzd2lwZXIub25Ub3VjaEVuZCwge1xuICAgIHBhc3NpdmU6IHRydWVcbiAgfSk7XG4gIGRvY3VtZW50W2RvbU1ldGhvZF0oJ3BvaW50ZXJjYW5jZWwnLCBzd2lwZXIub25Ub3VjaEVuZCwge1xuICAgIHBhc3NpdmU6IHRydWVcbiAgfSk7XG4gIGRvY3VtZW50W2RvbU1ldGhvZF0oJ3RvdWNoY2FuY2VsJywgc3dpcGVyLm9uVG91Y2hFbmQsIHtcbiAgICBwYXNzaXZlOiB0cnVlXG4gIH0pO1xuICBkb2N1bWVudFtkb21NZXRob2RdKCdwb2ludGVyb3V0Jywgc3dpcGVyLm9uVG91Y2hFbmQsIHtcbiAgICBwYXNzaXZlOiB0cnVlXG4gIH0pO1xuICBkb2N1bWVudFtkb21NZXRob2RdKCdwb2ludGVybGVhdmUnLCBzd2lwZXIub25Ub3VjaEVuZCwge1xuICAgIHBhc3NpdmU6IHRydWVcbiAgfSk7XG4gIGRvY3VtZW50W2RvbU1ldGhvZF0oJ2NvbnRleHRtZW51Jywgc3dpcGVyLm9uVG91Y2hFbmQsIHtcbiAgICBwYXNzaXZlOiB0cnVlXG4gIH0pO1xuXG4gIC8vIFByZXZlbnQgTGlua3MgQ2xpY2tzXG4gIGlmIChwYXJhbXMucHJldmVudENsaWNrcyB8fCBwYXJhbXMucHJldmVudENsaWNrc1Byb3BhZ2F0aW9uKSB7XG4gICAgZWxbZG9tTWV0aG9kXSgnY2xpY2snLCBzd2lwZXIub25DbGljaywgdHJ1ZSk7XG4gIH1cbiAgaWYgKHBhcmFtcy5jc3NNb2RlKSB7XG4gICAgd3JhcHBlckVsW2RvbU1ldGhvZF0oJ3Njcm9sbCcsIHN3aXBlci5vblNjcm9sbCk7XG4gIH1cblxuICAvLyBSZXNpemUgaGFuZGxlclxuICBpZiAocGFyYW1zLnVwZGF0ZU9uV2luZG93UmVzaXplKSB7XG4gICAgc3dpcGVyW3N3aXBlck1ldGhvZF0oZGV2aWNlLmlvcyB8fCBkZXZpY2UuYW5kcm9pZCA/ICdyZXNpemUgb3JpZW50YXRpb25jaGFuZ2Ugb2JzZXJ2ZXJVcGRhdGUnIDogJ3Jlc2l6ZSBvYnNlcnZlclVwZGF0ZScsIG9uUmVzaXplLCB0cnVlKTtcbiAgfSBlbHNlIHtcbiAgICBzd2lwZXJbc3dpcGVyTWV0aG9kXSgnb2JzZXJ2ZXJVcGRhdGUnLCBvblJlc2l6ZSwgdHJ1ZSk7XG4gIH1cblxuICAvLyBJbWFnZXMgbG9hZGVyXG4gIGVsW2RvbU1ldGhvZF0oJ2xvYWQnLCBzd2lwZXIub25Mb2FkLCB7XG4gICAgY2FwdHVyZTogdHJ1ZVxuICB9KTtcbn07XG5mdW5jdGlvbiBhdHRhY2hFdmVudHMoKSB7XG4gIGNvbnN0IHN3aXBlciA9IHRoaXM7XG4gIGNvbnN0IHtcbiAgICBwYXJhbXNcbiAgfSA9IHN3aXBlcjtcbiAgc3dpcGVyLm9uVG91Y2hTdGFydCA9IG9uVG91Y2hTdGFydC5iaW5kKHN3aXBlcik7XG4gIHN3aXBlci5vblRvdWNoTW92ZSA9IG9uVG91Y2hNb3ZlLmJpbmQoc3dpcGVyKTtcbiAgc3dpcGVyLm9uVG91Y2hFbmQgPSBvblRvdWNoRW5kLmJpbmQoc3dpcGVyKTtcbiAgc3dpcGVyLm9uRG9jdW1lbnRUb3VjaFN0YXJ0ID0gb25Eb2N1bWVudFRvdWNoU3RhcnQuYmluZChzd2lwZXIpO1xuICBpZiAocGFyYW1zLmNzc01vZGUpIHtcbiAgICBzd2lwZXIub25TY3JvbGwgPSBvblNjcm9sbC5iaW5kKHN3aXBlcik7XG4gIH1cbiAgc3dpcGVyLm9uQ2xpY2sgPSBvbkNsaWNrLmJpbmQoc3dpcGVyKTtcbiAgc3dpcGVyLm9uTG9hZCA9IG9uTG9hZC5iaW5kKHN3aXBlcik7XG4gIGV2ZW50cyhzd2lwZXIsICdvbicpO1xufVxuZnVuY3Rpb24gZGV0YWNoRXZlbnRzKCkge1xuICBjb25zdCBzd2lwZXIgPSB0aGlzO1xuICBldmVudHMoc3dpcGVyLCAnb2ZmJyk7XG59XG52YXIgZXZlbnRzJDEgPSB7XG4gIGF0dGFjaEV2ZW50cyxcbiAgZGV0YWNoRXZlbnRzXG59O1xuXG5jb25zdCBpc0dyaWRFbmFibGVkID0gKHN3aXBlciwgcGFyYW1zKSA9PiB7XG4gIHJldHVybiBzd2lwZXIuZ3JpZCAmJiBwYXJhbXMuZ3JpZCAmJiBwYXJhbXMuZ3JpZC5yb3dzID4gMTtcbn07XG5mdW5jdGlvbiBzZXRCcmVha3BvaW50KCkge1xuICBjb25zdCBzd2lwZXIgPSB0aGlzO1xuICBjb25zdCB7XG4gICAgcmVhbEluZGV4LFxuICAgIGluaXRpYWxpemVkLFxuICAgIHBhcmFtcyxcbiAgICBlbFxuICB9ID0gc3dpcGVyO1xuICBjb25zdCBicmVha3BvaW50cyA9IHBhcmFtcy5icmVha3BvaW50cztcbiAgaWYgKCFicmVha3BvaW50cyB8fCBicmVha3BvaW50cyAmJiBPYmplY3Qua2V5cyhicmVha3BvaW50cykubGVuZ3RoID09PSAwKSByZXR1cm47XG5cbiAgLy8gR2V0IGJyZWFrcG9pbnQgZm9yIHdpbmRvdyB3aWR0aCBhbmQgdXBkYXRlIHBhcmFtZXRlcnNcbiAgY29uc3QgYnJlYWtwb2ludCA9IHN3aXBlci5nZXRCcmVha3BvaW50KGJyZWFrcG9pbnRzLCBzd2lwZXIucGFyYW1zLmJyZWFrcG9pbnRzQmFzZSwgc3dpcGVyLmVsKTtcbiAgaWYgKCFicmVha3BvaW50IHx8IHN3aXBlci5jdXJyZW50QnJlYWtwb2ludCA9PT0gYnJlYWtwb2ludCkgcmV0dXJuO1xuICBjb25zdCBicmVha3BvaW50T25seVBhcmFtcyA9IGJyZWFrcG9pbnQgaW4gYnJlYWtwb2ludHMgPyBicmVha3BvaW50c1ticmVha3BvaW50XSA6IHVuZGVmaW5lZDtcbiAgY29uc3QgYnJlYWtwb2ludFBhcmFtcyA9IGJyZWFrcG9pbnRPbmx5UGFyYW1zIHx8IHN3aXBlci5vcmlnaW5hbFBhcmFtcztcbiAgY29uc3Qgd2FzTXVsdGlSb3cgPSBpc0dyaWRFbmFibGVkKHN3aXBlciwgcGFyYW1zKTtcbiAgY29uc3QgaXNNdWx0aVJvdyA9IGlzR3JpZEVuYWJsZWQoc3dpcGVyLCBicmVha3BvaW50UGFyYW1zKTtcbiAgY29uc3Qgd2FzR3JhYkN1cnNvciA9IHN3aXBlci5wYXJhbXMuZ3JhYkN1cnNvcjtcbiAgY29uc3QgaXNHcmFiQ3Vyc29yID0gYnJlYWtwb2ludFBhcmFtcy5ncmFiQ3Vyc29yO1xuICBjb25zdCB3YXNFbmFibGVkID0gcGFyYW1zLmVuYWJsZWQ7XG4gIGlmICh3YXNNdWx0aVJvdyAmJiAhaXNNdWx0aVJvdykge1xuICAgIGVsLmNsYXNzTGlzdC5yZW1vdmUoYCR7cGFyYW1zLmNvbnRhaW5lck1vZGlmaWVyQ2xhc3N9Z3JpZGAsIGAke3BhcmFtcy5jb250YWluZXJNb2RpZmllckNsYXNzfWdyaWQtY29sdW1uYCk7XG4gICAgc3dpcGVyLmVtaXRDb250YWluZXJDbGFzc2VzKCk7XG4gIH0gZWxzZSBpZiAoIXdhc011bHRpUm93ICYmIGlzTXVsdGlSb3cpIHtcbiAgICBlbC5jbGFzc0xpc3QuYWRkKGAke3BhcmFtcy5jb250YWluZXJNb2RpZmllckNsYXNzfWdyaWRgKTtcbiAgICBpZiAoYnJlYWtwb2ludFBhcmFtcy5ncmlkLmZpbGwgJiYgYnJlYWtwb2ludFBhcmFtcy5ncmlkLmZpbGwgPT09ICdjb2x1bW4nIHx8ICFicmVha3BvaW50UGFyYW1zLmdyaWQuZmlsbCAmJiBwYXJhbXMuZ3JpZC5maWxsID09PSAnY29sdW1uJykge1xuICAgICAgZWwuY2xhc3NMaXN0LmFkZChgJHtwYXJhbXMuY29udGFpbmVyTW9kaWZpZXJDbGFzc31ncmlkLWNvbHVtbmApO1xuICAgIH1cbiAgICBzd2lwZXIuZW1pdENvbnRhaW5lckNsYXNzZXMoKTtcbiAgfVxuICBpZiAod2FzR3JhYkN1cnNvciAmJiAhaXNHcmFiQ3Vyc29yKSB7XG4gICAgc3dpcGVyLnVuc2V0R3JhYkN1cnNvcigpO1xuICB9IGVsc2UgaWYgKCF3YXNHcmFiQ3Vyc29yICYmIGlzR3JhYkN1cnNvcikge1xuICAgIHN3aXBlci5zZXRHcmFiQ3Vyc29yKCk7XG4gIH1cblxuICAvLyBUb2dnbGUgbmF2aWdhdGlvbiwgcGFnaW5hdGlvbiwgc2Nyb2xsYmFyXG4gIFsnbmF2aWdhdGlvbicsICdwYWdpbmF0aW9uJywgJ3Njcm9sbGJhciddLmZvckVhY2gocHJvcCA9PiB7XG4gICAgaWYgKHR5cGVvZiBicmVha3BvaW50UGFyYW1zW3Byb3BdID09PSAndW5kZWZpbmVkJykgcmV0dXJuO1xuICAgIGNvbnN0IHdhc01vZHVsZUVuYWJsZWQgPSBwYXJhbXNbcHJvcF0gJiYgcGFyYW1zW3Byb3BdLmVuYWJsZWQ7XG4gICAgY29uc3QgaXNNb2R1bGVFbmFibGVkID0gYnJlYWtwb2ludFBhcmFtc1twcm9wXSAmJiBicmVha3BvaW50UGFyYW1zW3Byb3BdLmVuYWJsZWQ7XG4gICAgaWYgKHdhc01vZHVsZUVuYWJsZWQgJiYgIWlzTW9kdWxlRW5hYmxlZCkge1xuICAgICAgc3dpcGVyW3Byb3BdLmRpc2FibGUoKTtcbiAgICB9XG4gICAgaWYgKCF3YXNNb2R1bGVFbmFibGVkICYmIGlzTW9kdWxlRW5hYmxlZCkge1xuICAgICAgc3dpcGVyW3Byb3BdLmVuYWJsZSgpO1xuICAgIH1cbiAgfSk7XG4gIGNvbnN0IGRpcmVjdGlvbkNoYW5nZWQgPSBicmVha3BvaW50UGFyYW1zLmRpcmVjdGlvbiAmJiBicmVha3BvaW50UGFyYW1zLmRpcmVjdGlvbiAhPT0gcGFyYW1zLmRpcmVjdGlvbjtcbiAgY29uc3QgbmVlZHNSZUxvb3AgPSBwYXJhbXMubG9vcCAmJiAoYnJlYWtwb2ludFBhcmFtcy5zbGlkZXNQZXJWaWV3ICE9PSBwYXJhbXMuc2xpZGVzUGVyVmlldyB8fCBkaXJlY3Rpb25DaGFuZ2VkKTtcbiAgY29uc3Qgd2FzTG9vcCA9IHBhcmFtcy5sb29wO1xuICBpZiAoZGlyZWN0aW9uQ2hhbmdlZCAmJiBpbml0aWFsaXplZCkge1xuICAgIHN3aXBlci5jaGFuZ2VEaXJlY3Rpb24oKTtcbiAgfVxuICBleHRlbmQoc3dpcGVyLnBhcmFtcywgYnJlYWtwb2ludFBhcmFtcyk7XG4gIGNvbnN0IGlzRW5hYmxlZCA9IHN3aXBlci5wYXJhbXMuZW5hYmxlZDtcbiAgY29uc3QgaGFzTG9vcCA9IHN3aXBlci5wYXJhbXMubG9vcDtcbiAgT2JqZWN0LmFzc2lnbihzd2lwZXIsIHtcbiAgICBhbGxvd1RvdWNoTW92ZTogc3dpcGVyLnBhcmFtcy5hbGxvd1RvdWNoTW92ZSxcbiAgICBhbGxvd1NsaWRlTmV4dDogc3dpcGVyLnBhcmFtcy5hbGxvd1NsaWRlTmV4dCxcbiAgICBhbGxvd1NsaWRlUHJldjogc3dpcGVyLnBhcmFtcy5hbGxvd1NsaWRlUHJldlxuICB9KTtcbiAgaWYgKHdhc0VuYWJsZWQgJiYgIWlzRW5hYmxlZCkge1xuICAgIHN3aXBlci5kaXNhYmxlKCk7XG4gIH0gZWxzZSBpZiAoIXdhc0VuYWJsZWQgJiYgaXNFbmFibGVkKSB7XG4gICAgc3dpcGVyLmVuYWJsZSgpO1xuICB9XG4gIHN3aXBlci5jdXJyZW50QnJlYWtwb2ludCA9IGJyZWFrcG9pbnQ7XG4gIHN3aXBlci5lbWl0KCdfYmVmb3JlQnJlYWtwb2ludCcsIGJyZWFrcG9pbnRQYXJhbXMpO1xuICBpZiAoaW5pdGlhbGl6ZWQpIHtcbiAgICBpZiAobmVlZHNSZUxvb3ApIHtcbiAgICAgIHN3aXBlci5sb29wRGVzdHJveSgpO1xuICAgICAgc3dpcGVyLmxvb3BDcmVhdGUocmVhbEluZGV4KTtcbiAgICAgIHN3aXBlci51cGRhdGVTbGlkZXMoKTtcbiAgICB9IGVsc2UgaWYgKCF3YXNMb29wICYmIGhhc0xvb3ApIHtcbiAgICAgIHN3aXBlci5sb29wQ3JlYXRlKHJlYWxJbmRleCk7XG4gICAgICBzd2lwZXIudXBkYXRlU2xpZGVzKCk7XG4gICAgfSBlbHNlIGlmICh3YXNMb29wICYmICFoYXNMb29wKSB7XG4gICAgICBzd2lwZXIubG9vcERlc3Ryb3koKTtcbiAgICB9XG4gIH1cbiAgc3dpcGVyLmVtaXQoJ2JyZWFrcG9pbnQnLCBicmVha3BvaW50UGFyYW1zKTtcbn1cblxuZnVuY3Rpb24gZ2V0QnJlYWtwb2ludChicmVha3BvaW50cywgYmFzZSwgY29udGFpbmVyRWwpIHtcbiAgaWYgKGJhc2UgPT09IHZvaWQgMCkge1xuICAgIGJhc2UgPSAnd2luZG93JztcbiAgfVxuICBpZiAoIWJyZWFrcG9pbnRzIHx8IGJhc2UgPT09ICdjb250YWluZXInICYmICFjb250YWluZXJFbCkgcmV0dXJuIHVuZGVmaW5lZDtcbiAgbGV0IGJyZWFrcG9pbnQgPSBmYWxzZTtcbiAgY29uc3Qgd2luZG93ID0gZ2V0V2luZG93KCk7XG4gIGNvbnN0IGN1cnJlbnRIZWlnaHQgPSBiYXNlID09PSAnd2luZG93JyA/IHdpbmRvdy5pbm5lckhlaWdodCA6IGNvbnRhaW5lckVsLmNsaWVudEhlaWdodDtcbiAgY29uc3QgcG9pbnRzID0gT2JqZWN0LmtleXMoYnJlYWtwb2ludHMpLm1hcChwb2ludCA9PiB7XG4gICAgaWYgKHR5cGVvZiBwb2ludCA9PT0gJ3N0cmluZycgJiYgcG9pbnQuaW5kZXhPZignQCcpID09PSAwKSB7XG4gICAgICBjb25zdCBtaW5SYXRpbyA9IHBhcnNlRmxvYXQocG9pbnQuc3Vic3RyKDEpKTtcbiAgICAgIGNvbnN0IHZhbHVlID0gY3VycmVudEhlaWdodCAqIG1pblJhdGlvO1xuICAgICAgcmV0dXJuIHtcbiAgICAgICAgdmFsdWUsXG4gICAgICAgIHBvaW50XG4gICAgICB9O1xuICAgIH1cbiAgICByZXR1cm4ge1xuICAgICAgdmFsdWU6IHBvaW50LFxuICAgICAgcG9pbnRcbiAgICB9O1xuICB9KTtcbiAgcG9pbnRzLnNvcnQoKGEsIGIpID0+IHBhcnNlSW50KGEudmFsdWUsIDEwKSAtIHBhcnNlSW50KGIudmFsdWUsIDEwKSk7XG4gIGZvciAobGV0IGkgPSAwOyBpIDwgcG9pbnRzLmxlbmd0aDsgaSArPSAxKSB7XG4gICAgY29uc3Qge1xuICAgICAgcG9pbnQsXG4gICAgICB2YWx1ZVxuICAgIH0gPSBwb2ludHNbaV07XG4gICAgaWYgKGJhc2UgPT09ICd3aW5kb3cnKSB7XG4gICAgICBpZiAod2luZG93Lm1hdGNoTWVkaWEoYChtaW4td2lkdGg6ICR7dmFsdWV9cHgpYCkubWF0Y2hlcykge1xuICAgICAgICBicmVha3BvaW50ID0gcG9pbnQ7XG4gICAgICB9XG4gICAgfSBlbHNlIGlmICh2YWx1ZSA8PSBjb250YWluZXJFbC5jbGllbnRXaWR0aCkge1xuICAgICAgYnJlYWtwb2ludCA9IHBvaW50O1xuICAgIH1cbiAgfVxuICByZXR1cm4gYnJlYWtwb2ludCB8fCAnbWF4Jztcbn1cblxudmFyIGJyZWFrcG9pbnRzID0ge1xuICBzZXRCcmVha3BvaW50LFxuICBnZXRCcmVha3BvaW50XG59O1xuXG5mdW5jdGlvbiBwcmVwYXJlQ2xhc3NlcyhlbnRyaWVzLCBwcmVmaXgpIHtcbiAgY29uc3QgcmVzdWx0Q2xhc3NlcyA9IFtdO1xuICBlbnRyaWVzLmZvckVhY2goaXRlbSA9PiB7XG4gICAgaWYgKHR5cGVvZiBpdGVtID09PSAnb2JqZWN0Jykge1xuICAgICAgT2JqZWN0LmtleXMoaXRlbSkuZm9yRWFjaChjbGFzc05hbWVzID0+IHtcbiAgICAgICAgaWYgKGl0ZW1bY2xhc3NOYW1lc10pIHtcbiAgICAgICAgICByZXN1bHRDbGFzc2VzLnB1c2gocHJlZml4ICsgY2xhc3NOYW1lcyk7XG4gICAgICAgIH1cbiAgICAgIH0pO1xuICAgIH0gZWxzZSBpZiAodHlwZW9mIGl0ZW0gPT09ICdzdHJpbmcnKSB7XG4gICAgICByZXN1bHRDbGFzc2VzLnB1c2gocHJlZml4ICsgaXRlbSk7XG4gICAgfVxuICB9KTtcbiAgcmV0dXJuIHJlc3VsdENsYXNzZXM7XG59XG5mdW5jdGlvbiBhZGRDbGFzc2VzKCkge1xuICBjb25zdCBzd2lwZXIgPSB0aGlzO1xuICBjb25zdCB7XG4gICAgY2xhc3NOYW1lcyxcbiAgICBwYXJhbXMsXG4gICAgcnRsLFxuICAgIGVsLFxuICAgIGRldmljZVxuICB9ID0gc3dpcGVyO1xuICAvLyBwcmV0dGllci1pZ25vcmVcbiAgY29uc3Qgc3VmZml4ZXMgPSBwcmVwYXJlQ2xhc3NlcyhbJ2luaXRpYWxpemVkJywgcGFyYW1zLmRpcmVjdGlvbiwge1xuICAgICdmcmVlLW1vZGUnOiBzd2lwZXIucGFyYW1zLmZyZWVNb2RlICYmIHBhcmFtcy5mcmVlTW9kZS5lbmFibGVkXG4gIH0sIHtcbiAgICAnYXV0b2hlaWdodCc6IHBhcmFtcy5hdXRvSGVpZ2h0XG4gIH0sIHtcbiAgICAncnRsJzogcnRsXG4gIH0sIHtcbiAgICAnZ3JpZCc6IHBhcmFtcy5ncmlkICYmIHBhcmFtcy5ncmlkLnJvd3MgPiAxXG4gIH0sIHtcbiAgICAnZ3JpZC1jb2x1bW4nOiBwYXJhbXMuZ3JpZCAmJiBwYXJhbXMuZ3JpZC5yb3dzID4gMSAmJiBwYXJhbXMuZ3JpZC5maWxsID09PSAnY29sdW1uJ1xuICB9LCB7XG4gICAgJ2FuZHJvaWQnOiBkZXZpY2UuYW5kcm9pZFxuICB9LCB7XG4gICAgJ2lvcyc6IGRldmljZS5pb3NcbiAgfSwge1xuICAgICdjc3MtbW9kZSc6IHBhcmFtcy5jc3NNb2RlXG4gIH0sIHtcbiAgICAnY2VudGVyZWQnOiBwYXJhbXMuY3NzTW9kZSAmJiBwYXJhbXMuY2VudGVyZWRTbGlkZXNcbiAgfSwge1xuICAgICd3YXRjaC1wcm9ncmVzcyc6IHBhcmFtcy53YXRjaFNsaWRlc1Byb2dyZXNzXG4gIH1dLCBwYXJhbXMuY29udGFpbmVyTW9kaWZpZXJDbGFzcyk7XG4gIGNsYXNzTmFtZXMucHVzaCguLi5zdWZmaXhlcyk7XG4gIGVsLmNsYXNzTGlzdC5hZGQoLi4uY2xhc3NOYW1lcyk7XG4gIHN3aXBlci5lbWl0Q29udGFpbmVyQ2xhc3NlcygpO1xufVxuXG5mdW5jdGlvbiByZW1vdmVDbGFzc2VzKCkge1xuICBjb25zdCBzd2lwZXIgPSB0aGlzO1xuICBjb25zdCB7XG4gICAgZWwsXG4gICAgY2xhc3NOYW1lc1xuICB9ID0gc3dpcGVyO1xuICBpZiAoIWVsIHx8IHR5cGVvZiBlbCA9PT0gJ3N0cmluZycpIHJldHVybjtcbiAgZWwuY2xhc3NMaXN0LnJlbW92ZSguLi5jbGFzc05hbWVzKTtcbiAgc3dpcGVyLmVtaXRDb250YWluZXJDbGFzc2VzKCk7XG59XG5cbnZhciBjbGFzc2VzID0ge1xuICBhZGRDbGFzc2VzLFxuICByZW1vdmVDbGFzc2VzXG59O1xuXG5mdW5jdGlvbiBjaGVja092ZXJmbG93KCkge1xuICBjb25zdCBzd2lwZXIgPSB0aGlzO1xuICBjb25zdCB7XG4gICAgaXNMb2NrZWQ6IHdhc0xvY2tlZCxcbiAgICBwYXJhbXNcbiAgfSA9IHN3aXBlcjtcbiAgY29uc3Qge1xuICAgIHNsaWRlc09mZnNldEJlZm9yZVxuICB9ID0gcGFyYW1zO1xuICBpZiAoc2xpZGVzT2Zmc2V0QmVmb3JlKSB7XG4gICAgY29uc3QgbGFzdFNsaWRlSW5kZXggPSBzd2lwZXIuc2xpZGVzLmxlbmd0aCAtIDE7XG4gICAgY29uc3QgbGFzdFNsaWRlUmlnaHRFZGdlID0gc3dpcGVyLnNsaWRlc0dyaWRbbGFzdFNsaWRlSW5kZXhdICsgc3dpcGVyLnNsaWRlc1NpemVzR3JpZFtsYXN0U2xpZGVJbmRleF0gKyBzbGlkZXNPZmZzZXRCZWZvcmUgKiAyO1xuICAgIHN3aXBlci5pc0xvY2tlZCA9IHN3aXBlci5zaXplID4gbGFzdFNsaWRlUmlnaHRFZGdlO1xuICB9IGVsc2Uge1xuICAgIHN3aXBlci5pc0xvY2tlZCA9IHN3aXBlci5zbmFwR3JpZC5sZW5ndGggPT09IDE7XG4gIH1cbiAgaWYgKHBhcmFtcy5hbGxvd1NsaWRlTmV4dCA9PT0gdHJ1ZSkge1xuICAgIHN3aXBlci5hbGxvd1NsaWRlTmV4dCA9ICFzd2lwZXIuaXNMb2NrZWQ7XG4gIH1cbiAgaWYgKHBhcmFtcy5hbGxvd1NsaWRlUHJldiA9PT0gdHJ1ZSkge1xuICAgIHN3aXBlci5hbGxvd1NsaWRlUHJldiA9ICFzd2lwZXIuaXNMb2NrZWQ7XG4gIH1cbiAgaWYgKHdhc0xvY2tlZCAmJiB3YXNMb2NrZWQgIT09IHN3aXBlci5pc0xvY2tlZCkge1xuICAgIHN3aXBlci5pc0VuZCA9IGZhbHNlO1xuICB9XG4gIGlmICh3YXNMb2NrZWQgIT09IHN3aXBlci5pc0xvY2tlZCkge1xuICAgIHN3aXBlci5lbWl0KHN3aXBlci5pc0xvY2tlZCA/ICdsb2NrJyA6ICd1bmxvY2snKTtcbiAgfVxufVxudmFyIGNoZWNrT3ZlcmZsb3ckMSA9IHtcbiAgY2hlY2tPdmVyZmxvd1xufTtcblxudmFyIGRlZmF1bHRzID0ge1xuICBpbml0OiB0cnVlLFxuICBkaXJlY3Rpb246ICdob3Jpem9udGFsJyxcbiAgb25lV2F5TW92ZW1lbnQ6IGZhbHNlLFxuICBzd2lwZXJFbGVtZW50Tm9kZU5hbWU6ICdTV0lQRVItQ09OVEFJTkVSJyxcbiAgdG91Y2hFdmVudHNUYXJnZXQ6ICd3cmFwcGVyJyxcbiAgaW5pdGlhbFNsaWRlOiAwLFxuICBzcGVlZDogMzAwLFxuICBjc3NNb2RlOiBmYWxzZSxcbiAgdXBkYXRlT25XaW5kb3dSZXNpemU6IHRydWUsXG4gIHJlc2l6ZU9ic2VydmVyOiB0cnVlLFxuICBuZXN0ZWQ6IGZhbHNlLFxuICBjcmVhdGVFbGVtZW50czogZmFsc2UsXG4gIGV2ZW50c1ByZWZpeDogJ3N3aXBlcicsXG4gIGVuYWJsZWQ6IHRydWUsXG4gIGZvY3VzYWJsZUVsZW1lbnRzOiAnaW5wdXQsIHNlbGVjdCwgb3B0aW9uLCB0ZXh0YXJlYSwgYnV0dG9uLCB2aWRlbywgbGFiZWwnLFxuICAvLyBPdmVycmlkZXNcbiAgd2lkdGg6IG51bGwsXG4gIGhlaWdodDogbnVsbCxcbiAgLy9cbiAgcHJldmVudEludGVyYWN0aW9uT25UcmFuc2l0aW9uOiBmYWxzZSxcbiAgLy8gc3NyXG4gIHVzZXJBZ2VudDogbnVsbCxcbiAgdXJsOiBudWxsLFxuICAvLyBUbyBzdXBwb3J0IGlPUydzIHN3aXBlLXRvLWdvLWJhY2sgZ2VzdHVyZSAod2hlbiBiZWluZyB1c2VkIGluLWFwcCkuXG4gIGVkZ2VTd2lwZURldGVjdGlvbjogZmFsc2UsXG4gIGVkZ2VTd2lwZVRocmVzaG9sZDogMjAsXG4gIC8vIEF1dG9oZWlnaHRcbiAgYXV0b0hlaWdodDogZmFsc2UsXG4gIC8vIFNldCB3cmFwcGVyIHdpZHRoXG4gIHNldFdyYXBwZXJTaXplOiBmYWxzZSxcbiAgLy8gVmlydHVhbCBUcmFuc2xhdGVcbiAgdmlydHVhbFRyYW5zbGF0ZTogZmFsc2UsXG4gIC8vIEVmZmVjdHNcbiAgZWZmZWN0OiAnc2xpZGUnLFxuICAvLyAnc2xpZGUnIG9yICdmYWRlJyBvciAnY3ViZScgb3IgJ2NvdmVyZmxvdycgb3IgJ2ZsaXAnXG5cbiAgLy8gQnJlYWtwb2ludHNcbiAgYnJlYWtwb2ludHM6IHVuZGVmaW5lZCxcbiAgYnJlYWtwb2ludHNCYXNlOiAnd2luZG93JyxcbiAgLy8gU2xpZGVzIGdyaWRcbiAgc3BhY2VCZXR3ZWVuOiAwLFxuICBzbGlkZXNQZXJWaWV3OiAxLFxuICBzbGlkZXNQZXJHcm91cDogMSxcbiAgc2xpZGVzUGVyR3JvdXBTa2lwOiAwLFxuICBzbGlkZXNQZXJHcm91cEF1dG86IGZhbHNlLFxuICBjZW50ZXJlZFNsaWRlczogZmFsc2UsXG4gIGNlbnRlcmVkU2xpZGVzQm91bmRzOiBmYWxzZSxcbiAgc2xpZGVzT2Zmc2V0QmVmb3JlOiAwLFxuICAvLyBpbiBweFxuICBzbGlkZXNPZmZzZXRBZnRlcjogMCxcbiAgLy8gaW4gcHhcbiAgbm9ybWFsaXplU2xpZGVJbmRleDogdHJ1ZSxcbiAgY2VudGVySW5zdWZmaWNpZW50U2xpZGVzOiBmYWxzZSxcbiAgLy8gRGlzYWJsZSBzd2lwZXIgYW5kIGhpZGUgbmF2aWdhdGlvbiB3aGVuIGNvbnRhaW5lciBub3Qgb3ZlcmZsb3dcbiAgd2F0Y2hPdmVyZmxvdzogdHJ1ZSxcbiAgLy8gUm91bmQgbGVuZ3RoXG4gIHJvdW5kTGVuZ3RoczogZmFsc2UsXG4gIC8vIFRvdWNoZXNcbiAgdG91Y2hSYXRpbzogMSxcbiAgdG91Y2hBbmdsZTogNDUsXG4gIHNpbXVsYXRlVG91Y2g6IHRydWUsXG4gIHNob3J0U3dpcGVzOiB0cnVlLFxuICBsb25nU3dpcGVzOiB0cnVlLFxuICBsb25nU3dpcGVzUmF0aW86IDAuNSxcbiAgbG9uZ1N3aXBlc01zOiAzMDAsXG4gIGZvbGxvd0ZpbmdlcjogdHJ1ZSxcbiAgYWxsb3dUb3VjaE1vdmU6IHRydWUsXG4gIHRocmVzaG9sZDogNSxcbiAgdG91Y2hNb3ZlU3RvcFByb3BhZ2F0aW9uOiBmYWxzZSxcbiAgdG91Y2hTdGFydFByZXZlbnREZWZhdWx0OiB0cnVlLFxuICB0b3VjaFN0YXJ0Rm9yY2VQcmV2ZW50RGVmYXVsdDogZmFsc2UsXG4gIHRvdWNoUmVsZWFzZU9uRWRnZXM6IGZhbHNlLFxuICAvLyBVbmlxdWUgTmF2aWdhdGlvbiBFbGVtZW50c1xuICB1bmlxdWVOYXZFbGVtZW50czogdHJ1ZSxcbiAgLy8gUmVzaXN0YW5jZVxuICByZXNpc3RhbmNlOiB0cnVlLFxuICByZXNpc3RhbmNlUmF0aW86IDAuODUsXG4gIC8vIFByb2dyZXNzXG4gIHdhdGNoU2xpZGVzUHJvZ3Jlc3M6IGZhbHNlLFxuICAvLyBDdXJzb3JcbiAgZ3JhYkN1cnNvcjogZmFsc2UsXG4gIC8vIENsaWNrc1xuICBwcmV2ZW50Q2xpY2tzOiB0cnVlLFxuICBwcmV2ZW50Q2xpY2tzUHJvcGFnYXRpb246IHRydWUsXG4gIHNsaWRlVG9DbGlja2VkU2xpZGU6IGZhbHNlLFxuICAvLyBsb29wXG4gIGxvb3A6IGZhbHNlLFxuICBsb29wQWRkQmxhbmtTbGlkZXM6IHRydWUsXG4gIGxvb3BBZGRpdGlvbmFsU2xpZGVzOiAwLFxuICBsb29wUHJldmVudHNTbGlkaW5nOiB0cnVlLFxuICAvLyByZXdpbmRcbiAgcmV3aW5kOiBmYWxzZSxcbiAgLy8gU3dpcGluZy9ubyBzd2lwaW5nXG4gIGFsbG93U2xpZGVQcmV2OiB0cnVlLFxuICBhbGxvd1NsaWRlTmV4dDogdHJ1ZSxcbiAgc3dpcGVIYW5kbGVyOiBudWxsLFxuICAvLyAnLnN3aXBlLWhhbmRsZXInLFxuICBub1N3aXBpbmc6IHRydWUsXG4gIG5vU3dpcGluZ0NsYXNzOiAnc3dpcGVyLW5vLXN3aXBpbmcnLFxuICBub1N3aXBpbmdTZWxlY3RvcjogbnVsbCxcbiAgLy8gUGFzc2l2ZSBMaXN0ZW5lcnNcbiAgcGFzc2l2ZUxpc3RlbmVyczogdHJ1ZSxcbiAgbWF4QmFja2ZhY2VIaWRkZW5TbGlkZXM6IDEwLFxuICAvLyBOU1xuICBjb250YWluZXJNb2RpZmllckNsYXNzOiAnc3dpcGVyLScsXG4gIC8vIE5FV1xuICBzbGlkZUNsYXNzOiAnc3dpcGVyLXNsaWRlJyxcbiAgc2xpZGVCbGFua0NsYXNzOiAnc3dpcGVyLXNsaWRlLWJsYW5rJyxcbiAgc2xpZGVBY3RpdmVDbGFzczogJ3N3aXBlci1zbGlkZS1hY3RpdmUnLFxuICBzbGlkZVZpc2libGVDbGFzczogJ3N3aXBlci1zbGlkZS12aXNpYmxlJyxcbiAgc2xpZGVGdWxseVZpc2libGVDbGFzczogJ3N3aXBlci1zbGlkZS1mdWxseS12aXNpYmxlJyxcbiAgc2xpZGVOZXh0Q2xhc3M6ICdzd2lwZXItc2xpZGUtbmV4dCcsXG4gIHNsaWRlUHJldkNsYXNzOiAnc3dpcGVyLXNsaWRlLXByZXYnLFxuICB3cmFwcGVyQ2xhc3M6ICdzd2lwZXItd3JhcHBlcicsXG4gIGxhenlQcmVsb2FkZXJDbGFzczogJ3N3aXBlci1sYXp5LXByZWxvYWRlcicsXG4gIGxhenlQcmVsb2FkUHJldk5leHQ6IDAsXG4gIC8vIENhbGxiYWNrc1xuICBydW5DYWxsYmFja3NPbkluaXQ6IHRydWUsXG4gIC8vIEludGVybmFsc1xuICBfZW1pdENsYXNzZXM6IGZhbHNlXG59O1xuXG5mdW5jdGlvbiBtb2R1bGVFeHRlbmRQYXJhbXMocGFyYW1zLCBhbGxNb2R1bGVzUGFyYW1zKSB7XG4gIHJldHVybiBmdW5jdGlvbiBleHRlbmRQYXJhbXMob2JqKSB7XG4gICAgaWYgKG9iaiA9PT0gdm9pZCAwKSB7XG4gICAgICBvYmogPSB7fTtcbiAgICB9XG4gICAgY29uc3QgbW9kdWxlUGFyYW1OYW1lID0gT2JqZWN0LmtleXMob2JqKVswXTtcbiAgICBjb25zdCBtb2R1bGVQYXJhbXMgPSBvYmpbbW9kdWxlUGFyYW1OYW1lXTtcbiAgICBpZiAodHlwZW9mIG1vZHVsZVBhcmFtcyAhPT0gJ29iamVjdCcgfHwgbW9kdWxlUGFyYW1zID09PSBudWxsKSB7XG4gICAgICBleHRlbmQoYWxsTW9kdWxlc1BhcmFtcywgb2JqKTtcbiAgICAgIHJldHVybjtcbiAgICB9XG4gICAgaWYgKHBhcmFtc1ttb2R1bGVQYXJhbU5hbWVdID09PSB0cnVlKSB7XG4gICAgICBwYXJhbXNbbW9kdWxlUGFyYW1OYW1lXSA9IHtcbiAgICAgICAgZW5hYmxlZDogdHJ1ZVxuICAgICAgfTtcbiAgICB9XG4gICAgaWYgKG1vZHVsZVBhcmFtTmFtZSA9PT0gJ25hdmlnYXRpb24nICYmIHBhcmFtc1ttb2R1bGVQYXJhbU5hbWVdICYmIHBhcmFtc1ttb2R1bGVQYXJhbU5hbWVdLmVuYWJsZWQgJiYgIXBhcmFtc1ttb2R1bGVQYXJhbU5hbWVdLnByZXZFbCAmJiAhcGFyYW1zW21vZHVsZVBhcmFtTmFtZV0ubmV4dEVsKSB7XG4gICAgICBwYXJhbXNbbW9kdWxlUGFyYW1OYW1lXS5hdXRvID0gdHJ1ZTtcbiAgICB9XG4gICAgaWYgKFsncGFnaW5hdGlvbicsICdzY3JvbGxiYXInXS5pbmRleE9mKG1vZHVsZVBhcmFtTmFtZSkgPj0gMCAmJiBwYXJhbXNbbW9kdWxlUGFyYW1OYW1lXSAmJiBwYXJhbXNbbW9kdWxlUGFyYW1OYW1lXS5lbmFibGVkICYmICFwYXJhbXNbbW9kdWxlUGFyYW1OYW1lXS5lbCkge1xuICAgICAgcGFyYW1zW21vZHVsZVBhcmFtTmFtZV0uYXV0byA9IHRydWU7XG4gICAgfVxuICAgIGlmICghKG1vZHVsZVBhcmFtTmFtZSBpbiBwYXJhbXMgJiYgJ2VuYWJsZWQnIGluIG1vZHVsZVBhcmFtcykpIHtcbiAgICAgIGV4dGVuZChhbGxNb2R1bGVzUGFyYW1zLCBvYmopO1xuICAgICAgcmV0dXJuO1xuICAgIH1cbiAgICBpZiAodHlwZW9mIHBhcmFtc1ttb2R1bGVQYXJhbU5hbWVdID09PSAnb2JqZWN0JyAmJiAhKCdlbmFibGVkJyBpbiBwYXJhbXNbbW9kdWxlUGFyYW1OYW1lXSkpIHtcbiAgICAgIHBhcmFtc1ttb2R1bGVQYXJhbU5hbWVdLmVuYWJsZWQgPSB0cnVlO1xuICAgIH1cbiAgICBpZiAoIXBhcmFtc1ttb2R1bGVQYXJhbU5hbWVdKSBwYXJhbXNbbW9kdWxlUGFyYW1OYW1lXSA9IHtcbiAgICAgIGVuYWJsZWQ6IGZhbHNlXG4gICAgfTtcbiAgICBleHRlbmQoYWxsTW9kdWxlc1BhcmFtcywgb2JqKTtcbiAgfTtcbn1cblxuLyogZXNsaW50IG5vLXBhcmFtLXJlYXNzaWduOiBcIm9mZlwiICovXG5jb25zdCBwcm90b3R5cGVzID0ge1xuICBldmVudHNFbWl0dGVyLFxuICB1cGRhdGUsXG4gIHRyYW5zbGF0ZSxcbiAgdHJhbnNpdGlvbixcbiAgc2xpZGUsXG4gIGxvb3AsXG4gIGdyYWJDdXJzb3IsXG4gIGV2ZW50czogZXZlbnRzJDEsXG4gIGJyZWFrcG9pbnRzLFxuICBjaGVja092ZXJmbG93OiBjaGVja092ZXJmbG93JDEsXG4gIGNsYXNzZXNcbn07XG5jb25zdCBleHRlbmRlZERlZmF1bHRzID0ge307XG5jbGFzcyBTd2lwZXIge1xuICBjb25zdHJ1Y3RvcigpIHtcbiAgICBsZXQgZWw7XG4gICAgbGV0IHBhcmFtcztcbiAgICBmb3IgKHZhciBfbGVuID0gYXJndW1lbnRzLmxlbmd0aCwgYXJncyA9IG5ldyBBcnJheShfbGVuKSwgX2tleSA9IDA7IF9rZXkgPCBfbGVuOyBfa2V5KyspIHtcbiAgICAgIGFyZ3NbX2tleV0gPSBhcmd1bWVudHNbX2tleV07XG4gICAgfVxuICAgIGlmIChhcmdzLmxlbmd0aCA9PT0gMSAmJiBhcmdzWzBdLmNvbnN0cnVjdG9yICYmIE9iamVjdC5wcm90b3R5cGUudG9TdHJpbmcuY2FsbChhcmdzWzBdKS5zbGljZSg4LCAtMSkgPT09ICdPYmplY3QnKSB7XG4gICAgICBwYXJhbXMgPSBhcmdzWzBdO1xuICAgIH0gZWxzZSB7XG4gICAgICBbZWwsIHBhcmFtc10gPSBhcmdzO1xuICAgIH1cbiAgICBpZiAoIXBhcmFtcykgcGFyYW1zID0ge307XG4gICAgcGFyYW1zID0gZXh0ZW5kKHt9LCBwYXJhbXMpO1xuICAgIGlmIChlbCAmJiAhcGFyYW1zLmVsKSBwYXJhbXMuZWwgPSBlbDtcbiAgICBjb25zdCBkb2N1bWVudCA9IGdldERvY3VtZW50KCk7XG4gICAgaWYgKHBhcmFtcy5lbCAmJiB0eXBlb2YgcGFyYW1zLmVsID09PSAnc3RyaW5nJyAmJiBkb2N1bWVudC5xdWVyeVNlbGVjdG9yQWxsKHBhcmFtcy5lbCkubGVuZ3RoID4gMSkge1xuICAgICAgY29uc3Qgc3dpcGVycyA9IFtdO1xuICAgICAgZG9jdW1lbnQucXVlcnlTZWxlY3RvckFsbChwYXJhbXMuZWwpLmZvckVhY2goY29udGFpbmVyRWwgPT4ge1xuICAgICAgICBjb25zdCBuZXdQYXJhbXMgPSBleHRlbmQoe30sIHBhcmFtcywge1xuICAgICAgICAgIGVsOiBjb250YWluZXJFbFxuICAgICAgICB9KTtcbiAgICAgICAgc3dpcGVycy5wdXNoKG5ldyBTd2lwZXIobmV3UGFyYW1zKSk7XG4gICAgICB9KTtcbiAgICAgIC8vIGVzbGludC1kaXNhYmxlLW5leHQtbGluZSBuby1jb25zdHJ1Y3Rvci1yZXR1cm5cbiAgICAgIHJldHVybiBzd2lwZXJzO1xuICAgIH1cblxuICAgIC8vIFN3aXBlciBJbnN0YW5jZVxuICAgIGNvbnN0IHN3aXBlciA9IHRoaXM7XG4gICAgc3dpcGVyLl9fc3dpcGVyX18gPSB0cnVlO1xuICAgIHN3aXBlci5zdXBwb3J0ID0gZ2V0U3VwcG9ydCgpO1xuICAgIHN3aXBlci5kZXZpY2UgPSBnZXREZXZpY2Uoe1xuICAgICAgdXNlckFnZW50OiBwYXJhbXMudXNlckFnZW50XG4gICAgfSk7XG4gICAgc3dpcGVyLmJyb3dzZXIgPSBnZXRCcm93c2VyKCk7XG4gICAgc3dpcGVyLmV2ZW50c0xpc3RlbmVycyA9IHt9O1xuICAgIHN3aXBlci5ldmVudHNBbnlMaXN0ZW5lcnMgPSBbXTtcbiAgICBzd2lwZXIubW9kdWxlcyA9IFsuLi5zd2lwZXIuX19tb2R1bGVzX19dO1xuICAgIGlmIChwYXJhbXMubW9kdWxlcyAmJiBBcnJheS5pc0FycmF5KHBhcmFtcy5tb2R1bGVzKSkge1xuICAgICAgc3dpcGVyLm1vZHVsZXMucHVzaCguLi5wYXJhbXMubW9kdWxlcyk7XG4gICAgfVxuICAgIGNvbnN0IGFsbE1vZHVsZXNQYXJhbXMgPSB7fTtcbiAgICBzd2lwZXIubW9kdWxlcy5mb3JFYWNoKG1vZCA9PiB7XG4gICAgICBtb2Qoe1xuICAgICAgICBwYXJhbXMsXG4gICAgICAgIHN3aXBlcixcbiAgICAgICAgZXh0ZW5kUGFyYW1zOiBtb2R1bGVFeHRlbmRQYXJhbXMocGFyYW1zLCBhbGxNb2R1bGVzUGFyYW1zKSxcbiAgICAgICAgb246IHN3aXBlci5vbi5iaW5kKHN3aXBlciksXG4gICAgICAgIG9uY2U6IHN3aXBlci5vbmNlLmJpbmQoc3dpcGVyKSxcbiAgICAgICAgb2ZmOiBzd2lwZXIub2ZmLmJpbmQoc3dpcGVyKSxcbiAgICAgICAgZW1pdDogc3dpcGVyLmVtaXQuYmluZChzd2lwZXIpXG4gICAgICB9KTtcbiAgICB9KTtcblxuICAgIC8vIEV4dGVuZCBkZWZhdWx0cyB3aXRoIG1vZHVsZXMgcGFyYW1zXG4gICAgY29uc3Qgc3dpcGVyUGFyYW1zID0gZXh0ZW5kKHt9LCBkZWZhdWx0cywgYWxsTW9kdWxlc1BhcmFtcyk7XG5cbiAgICAvLyBFeHRlbmQgZGVmYXVsdHMgd2l0aCBwYXNzZWQgcGFyYW1zXG4gICAgc3dpcGVyLnBhcmFtcyA9IGV4dGVuZCh7fSwgc3dpcGVyUGFyYW1zLCBleHRlbmRlZERlZmF1bHRzLCBwYXJhbXMpO1xuICAgIHN3aXBlci5vcmlnaW5hbFBhcmFtcyA9IGV4dGVuZCh7fSwgc3dpcGVyLnBhcmFtcyk7XG4gICAgc3dpcGVyLnBhc3NlZFBhcmFtcyA9IGV4dGVuZCh7fSwgcGFyYW1zKTtcblxuICAgIC8vIGFkZCBldmVudCBsaXN0ZW5lcnNcbiAgICBpZiAoc3dpcGVyLnBhcmFtcyAmJiBzd2lwZXIucGFyYW1zLm9uKSB7XG4gICAgICBPYmplY3Qua2V5cyhzd2lwZXIucGFyYW1zLm9uKS5mb3JFYWNoKGV2ZW50TmFtZSA9PiB7XG4gICAgICAgIHN3aXBlci5vbihldmVudE5hbWUsIHN3aXBlci5wYXJhbXMub25bZXZlbnROYW1lXSk7XG4gICAgICB9KTtcbiAgICB9XG4gICAgaWYgKHN3aXBlci5wYXJhbXMgJiYgc3dpcGVyLnBhcmFtcy5vbkFueSkge1xuICAgICAgc3dpcGVyLm9uQW55KHN3aXBlci5wYXJhbXMub25BbnkpO1xuICAgIH1cblxuICAgIC8vIEV4dGVuZCBTd2lwZXJcbiAgICBPYmplY3QuYXNzaWduKHN3aXBlciwge1xuICAgICAgZW5hYmxlZDogc3dpcGVyLnBhcmFtcy5lbmFibGVkLFxuICAgICAgZWwsXG4gICAgICAvLyBDbGFzc2VzXG4gICAgICBjbGFzc05hbWVzOiBbXSxcbiAgICAgIC8vIFNsaWRlc1xuICAgICAgc2xpZGVzOiBbXSxcbiAgICAgIHNsaWRlc0dyaWQ6IFtdLFxuICAgICAgc25hcEdyaWQ6IFtdLFxuICAgICAgc2xpZGVzU2l6ZXNHcmlkOiBbXSxcbiAgICAgIC8vIGlzRGlyZWN0aW9uXG4gICAgICBpc0hvcml6b250YWwoKSB7XG4gICAgICAgIHJldHVybiBzd2lwZXIucGFyYW1zLmRpcmVjdGlvbiA9PT0gJ2hvcml6b250YWwnO1xuICAgICAgfSxcbiAgICAgIGlzVmVydGljYWwoKSB7XG4gICAgICAgIHJldHVybiBzd2lwZXIucGFyYW1zLmRpcmVjdGlvbiA9PT0gJ3ZlcnRpY2FsJztcbiAgICAgIH0sXG4gICAgICAvLyBJbmRleGVzXG4gICAgICBhY3RpdmVJbmRleDogMCxcbiAgICAgIHJlYWxJbmRleDogMCxcbiAgICAgIC8vXG4gICAgICBpc0JlZ2lubmluZzogdHJ1ZSxcbiAgICAgIGlzRW5kOiBmYWxzZSxcbiAgICAgIC8vIFByb3BzXG4gICAgICB0cmFuc2xhdGU6IDAsXG4gICAgICBwcmV2aW91c1RyYW5zbGF0ZTogMCxcbiAgICAgIHByb2dyZXNzOiAwLFxuICAgICAgdmVsb2NpdHk6IDAsXG4gICAgICBhbmltYXRpbmc6IGZhbHNlLFxuICAgICAgY3NzT3ZlcmZsb3dBZGp1c3RtZW50KCkge1xuICAgICAgICAvLyBSZXR1cm5zIDAgdW5sZXNzIGB0cmFuc2xhdGVgIGlzID4gMioqMjNcbiAgICAgICAgLy8gU2hvdWxkIGJlIHN1YnRyYWN0ZWQgZnJvbSBjc3MgdmFsdWVzIHRvIHByZXZlbnQgb3ZlcmZsb3dcbiAgICAgICAgcmV0dXJuIE1hdGgudHJ1bmModGhpcy50cmFuc2xhdGUgLyAyICoqIDIzKSAqIDIgKiogMjM7XG4gICAgICB9LFxuICAgICAgLy8gTG9ja3NcbiAgICAgIGFsbG93U2xpZGVOZXh0OiBzd2lwZXIucGFyYW1zLmFsbG93U2xpZGVOZXh0LFxuICAgICAgYWxsb3dTbGlkZVByZXY6IHN3aXBlci5wYXJhbXMuYWxsb3dTbGlkZVByZXYsXG4gICAgICAvLyBUb3VjaCBFdmVudHNcbiAgICAgIHRvdWNoRXZlbnRzRGF0YToge1xuICAgICAgICBpc1RvdWNoZWQ6IHVuZGVmaW5lZCxcbiAgICAgICAgaXNNb3ZlZDogdW5kZWZpbmVkLFxuICAgICAgICBhbGxvd1RvdWNoQ2FsbGJhY2tzOiB1bmRlZmluZWQsXG4gICAgICAgIHRvdWNoU3RhcnRUaW1lOiB1bmRlZmluZWQsXG4gICAgICAgIGlzU2Nyb2xsaW5nOiB1bmRlZmluZWQsXG4gICAgICAgIGN1cnJlbnRUcmFuc2xhdGU6IHVuZGVmaW5lZCxcbiAgICAgICAgc3RhcnRUcmFuc2xhdGU6IHVuZGVmaW5lZCxcbiAgICAgICAgYWxsb3dUaHJlc2hvbGRNb3ZlOiB1bmRlZmluZWQsXG4gICAgICAgIC8vIEZvcm0gZWxlbWVudHMgdG8gbWF0Y2hcbiAgICAgICAgZm9jdXNhYmxlRWxlbWVudHM6IHN3aXBlci5wYXJhbXMuZm9jdXNhYmxlRWxlbWVudHMsXG4gICAgICAgIC8vIExhc3QgY2xpY2sgdGltZVxuICAgICAgICBsYXN0Q2xpY2tUaW1lOiAwLFxuICAgICAgICBjbGlja1RpbWVvdXQ6IHVuZGVmaW5lZCxcbiAgICAgICAgLy8gVmVsb2NpdGllc1xuICAgICAgICB2ZWxvY2l0aWVzOiBbXSxcbiAgICAgICAgYWxsb3dNb21lbnR1bUJvdW5jZTogdW5kZWZpbmVkLFxuICAgICAgICBzdGFydE1vdmluZzogdW5kZWZpbmVkLFxuICAgICAgICBwb2ludGVySWQ6IG51bGwsXG4gICAgICAgIHRvdWNoSWQ6IG51bGxcbiAgICAgIH0sXG4gICAgICAvLyBDbGlja3NcbiAgICAgIGFsbG93Q2xpY2s6IHRydWUsXG4gICAgICAvLyBUb3VjaGVzXG4gICAgICBhbGxvd1RvdWNoTW92ZTogc3dpcGVyLnBhcmFtcy5hbGxvd1RvdWNoTW92ZSxcbiAgICAgIHRvdWNoZXM6IHtcbiAgICAgICAgc3RhcnRYOiAwLFxuICAgICAgICBzdGFydFk6IDAsXG4gICAgICAgIGN1cnJlbnRYOiAwLFxuICAgICAgICBjdXJyZW50WTogMCxcbiAgICAgICAgZGlmZjogMFxuICAgICAgfSxcbiAgICAgIC8vIEltYWdlc1xuICAgICAgaW1hZ2VzVG9Mb2FkOiBbXSxcbiAgICAgIGltYWdlc0xvYWRlZDogMFxuICAgIH0pO1xuICAgIHN3aXBlci5lbWl0KCdfc3dpcGVyJyk7XG5cbiAgICAvLyBJbml0XG4gICAgaWYgKHN3aXBlci5wYXJhbXMuaW5pdCkge1xuICAgICAgc3dpcGVyLmluaXQoKTtcbiAgICB9XG5cbiAgICAvLyBSZXR1cm4gYXBwIGluc3RhbmNlXG4gICAgLy8gZXNsaW50LWRpc2FibGUtbmV4dC1saW5lIG5vLWNvbnN0cnVjdG9yLXJldHVyblxuICAgIHJldHVybiBzd2lwZXI7XG4gIH1cbiAgZ2V0RGlyZWN0aW9uTGFiZWwocHJvcGVydHkpIHtcbiAgICBpZiAodGhpcy5pc0hvcml6b250YWwoKSkge1xuICAgICAgcmV0dXJuIHByb3BlcnR5O1xuICAgIH1cbiAgICAvLyBwcmV0dGllci1pZ25vcmVcbiAgICByZXR1cm4ge1xuICAgICAgJ3dpZHRoJzogJ2hlaWdodCcsXG4gICAgICAnbWFyZ2luLXRvcCc6ICdtYXJnaW4tbGVmdCcsXG4gICAgICAnbWFyZ2luLWJvdHRvbSAnOiAnbWFyZ2luLXJpZ2h0JyxcbiAgICAgICdtYXJnaW4tbGVmdCc6ICdtYXJnaW4tdG9wJyxcbiAgICAgICdtYXJnaW4tcmlnaHQnOiAnbWFyZ2luLWJvdHRvbScsXG4gICAgICAncGFkZGluZy1sZWZ0JzogJ3BhZGRpbmctdG9wJyxcbiAgICAgICdwYWRkaW5nLXJpZ2h0JzogJ3BhZGRpbmctYm90dG9tJyxcbiAgICAgICdtYXJnaW5SaWdodCc6ICdtYXJnaW5Cb3R0b20nXG4gICAgfVtwcm9wZXJ0eV07XG4gIH1cbiAgZ2V0U2xpZGVJbmRleChzbGlkZUVsKSB7XG4gICAgY29uc3Qge1xuICAgICAgc2xpZGVzRWwsXG4gICAgICBwYXJhbXNcbiAgICB9ID0gdGhpcztcbiAgICBjb25zdCBzbGlkZXMgPSBlbGVtZW50Q2hpbGRyZW4oc2xpZGVzRWwsIGAuJHtwYXJhbXMuc2xpZGVDbGFzc30sIHN3aXBlci1zbGlkZWApO1xuICAgIGNvbnN0IGZpcnN0U2xpZGVJbmRleCA9IGVsZW1lbnRJbmRleChzbGlkZXNbMF0pO1xuICAgIHJldHVybiBlbGVtZW50SW5kZXgoc2xpZGVFbCkgLSBmaXJzdFNsaWRlSW5kZXg7XG4gIH1cbiAgZ2V0U2xpZGVJbmRleEJ5RGF0YShpbmRleCkge1xuICAgIHJldHVybiB0aGlzLmdldFNsaWRlSW5kZXgodGhpcy5zbGlkZXMuZmlsdGVyKHNsaWRlRWwgPT4gc2xpZGVFbC5nZXRBdHRyaWJ1dGUoJ2RhdGEtc3dpcGVyLXNsaWRlLWluZGV4JykgKiAxID09PSBpbmRleClbMF0pO1xuICB9XG4gIHJlY2FsY1NsaWRlcygpIHtcbiAgICBjb25zdCBzd2lwZXIgPSB0aGlzO1xuICAgIGNvbnN0IHtcbiAgICAgIHNsaWRlc0VsLFxuICAgICAgcGFyYW1zXG4gICAgfSA9IHN3aXBlcjtcbiAgICBzd2lwZXIuc2xpZGVzID0gZWxlbWVudENoaWxkcmVuKHNsaWRlc0VsLCBgLiR7cGFyYW1zLnNsaWRlQ2xhc3N9LCBzd2lwZXItc2xpZGVgKTtcbiAgfVxuICBlbmFibGUoKSB7XG4gICAgY29uc3Qgc3dpcGVyID0gdGhpcztcbiAgICBpZiAoc3dpcGVyLmVuYWJsZWQpIHJldHVybjtcbiAgICBzd2lwZXIuZW5hYmxlZCA9IHRydWU7XG4gICAgaWYgKHN3aXBlci5wYXJhbXMuZ3JhYkN1cnNvcikge1xuICAgICAgc3dpcGVyLnNldEdyYWJDdXJzb3IoKTtcbiAgICB9XG4gICAgc3dpcGVyLmVtaXQoJ2VuYWJsZScpO1xuICB9XG4gIGRpc2FibGUoKSB7XG4gICAgY29uc3Qgc3dpcGVyID0gdGhpcztcbiAgICBpZiAoIXN3aXBlci5lbmFibGVkKSByZXR1cm47XG4gICAgc3dpcGVyLmVuYWJsZWQgPSBmYWxzZTtcbiAgICBpZiAoc3dpcGVyLnBhcmFtcy5ncmFiQ3Vyc29yKSB7XG4gICAgICBzd2lwZXIudW5zZXRHcmFiQ3Vyc29yKCk7XG4gICAgfVxuICAgIHN3aXBlci5lbWl0KCdkaXNhYmxlJyk7XG4gIH1cbiAgc2V0UHJvZ3Jlc3MocHJvZ3Jlc3MsIHNwZWVkKSB7XG4gICAgY29uc3Qgc3dpcGVyID0gdGhpcztcbiAgICBwcm9ncmVzcyA9IE1hdGgubWluKE1hdGgubWF4KHByb2dyZXNzLCAwKSwgMSk7XG4gICAgY29uc3QgbWluID0gc3dpcGVyLm1pblRyYW5zbGF0ZSgpO1xuICAgIGNvbnN0IG1heCA9IHN3aXBlci5tYXhUcmFuc2xhdGUoKTtcbiAgICBjb25zdCBjdXJyZW50ID0gKG1heCAtIG1pbikgKiBwcm9ncmVzcyArIG1pbjtcbiAgICBzd2lwZXIudHJhbnNsYXRlVG8oY3VycmVudCwgdHlwZW9mIHNwZWVkID09PSAndW5kZWZpbmVkJyA/IDAgOiBzcGVlZCk7XG4gICAgc3dpcGVyLnVwZGF0ZUFjdGl2ZUluZGV4KCk7XG4gICAgc3dpcGVyLnVwZGF0ZVNsaWRlc0NsYXNzZXMoKTtcbiAgfVxuICBlbWl0Q29udGFpbmVyQ2xhc3NlcygpIHtcbiAgICBjb25zdCBzd2lwZXIgPSB0aGlzO1xuICAgIGlmICghc3dpcGVyLnBhcmFtcy5fZW1pdENsYXNzZXMgfHwgIXN3aXBlci5lbCkgcmV0dXJuO1xuICAgIGNvbnN0IGNscyA9IHN3aXBlci5lbC5jbGFzc05hbWUuc3BsaXQoJyAnKS5maWx0ZXIoY2xhc3NOYW1lID0+IHtcbiAgICAgIHJldHVybiBjbGFzc05hbWUuaW5kZXhPZignc3dpcGVyJykgPT09IDAgfHwgY2xhc3NOYW1lLmluZGV4T2Yoc3dpcGVyLnBhcmFtcy5jb250YWluZXJNb2RpZmllckNsYXNzKSA9PT0gMDtcbiAgICB9KTtcbiAgICBzd2lwZXIuZW1pdCgnX2NvbnRhaW5lckNsYXNzZXMnLCBjbHMuam9pbignICcpKTtcbiAgfVxuICBnZXRTbGlkZUNsYXNzZXMoc2xpZGVFbCkge1xuICAgIGNvbnN0IHN3aXBlciA9IHRoaXM7XG4gICAgaWYgKHN3aXBlci5kZXN0cm95ZWQpIHJldHVybiAnJztcbiAgICByZXR1cm4gc2xpZGVFbC5jbGFzc05hbWUuc3BsaXQoJyAnKS5maWx0ZXIoY2xhc3NOYW1lID0+IHtcbiAgICAgIHJldHVybiBjbGFzc05hbWUuaW5kZXhPZignc3dpcGVyLXNsaWRlJykgPT09IDAgfHwgY2xhc3NOYW1lLmluZGV4T2Yoc3dpcGVyLnBhcmFtcy5zbGlkZUNsYXNzKSA9PT0gMDtcbiAgICB9KS5qb2luKCcgJyk7XG4gIH1cbiAgZW1pdFNsaWRlc0NsYXNzZXMoKSB7XG4gICAgY29uc3Qgc3dpcGVyID0gdGhpcztcbiAgICBpZiAoIXN3aXBlci5wYXJhbXMuX2VtaXRDbGFzc2VzIHx8ICFzd2lwZXIuZWwpIHJldHVybjtcbiAgICBjb25zdCB1cGRhdGVzID0gW107XG4gICAgc3dpcGVyLnNsaWRlcy5mb3JFYWNoKHNsaWRlRWwgPT4ge1xuICAgICAgY29uc3QgY2xhc3NOYW1lcyA9IHN3aXBlci5nZXRTbGlkZUNsYXNzZXMoc2xpZGVFbCk7XG4gICAgICB1cGRhdGVzLnB1c2goe1xuICAgICAgICBzbGlkZUVsLFxuICAgICAgICBjbGFzc05hbWVzXG4gICAgICB9KTtcbiAgICAgIHN3aXBlci5lbWl0KCdfc2xpZGVDbGFzcycsIHNsaWRlRWwsIGNsYXNzTmFtZXMpO1xuICAgIH0pO1xuICAgIHN3aXBlci5lbWl0KCdfc2xpZGVDbGFzc2VzJywgdXBkYXRlcyk7XG4gIH1cbiAgc2xpZGVzUGVyVmlld0R5bmFtaWModmlldywgZXhhY3QpIHtcbiAgICBpZiAodmlldyA9PT0gdm9pZCAwKSB7XG4gICAgICB2aWV3ID0gJ2N1cnJlbnQnO1xuICAgIH1cbiAgICBpZiAoZXhhY3QgPT09IHZvaWQgMCkge1xuICAgICAgZXhhY3QgPSBmYWxzZTtcbiAgICB9XG4gICAgY29uc3Qgc3dpcGVyID0gdGhpcztcbiAgICBjb25zdCB7XG4gICAgICBwYXJhbXMsXG4gICAgICBzbGlkZXMsXG4gICAgICBzbGlkZXNHcmlkLFxuICAgICAgc2xpZGVzU2l6ZXNHcmlkLFxuICAgICAgc2l6ZTogc3dpcGVyU2l6ZSxcbiAgICAgIGFjdGl2ZUluZGV4XG4gICAgfSA9IHN3aXBlcjtcbiAgICBsZXQgc3B2ID0gMTtcbiAgICBpZiAodHlwZW9mIHBhcmFtcy5zbGlkZXNQZXJWaWV3ID09PSAnbnVtYmVyJykgcmV0dXJuIHBhcmFtcy5zbGlkZXNQZXJWaWV3O1xuICAgIGlmIChwYXJhbXMuY2VudGVyZWRTbGlkZXMpIHtcbiAgICAgIGxldCBzbGlkZVNpemUgPSBzbGlkZXNbYWN0aXZlSW5kZXhdID8gTWF0aC5jZWlsKHNsaWRlc1thY3RpdmVJbmRleF0uc3dpcGVyU2xpZGVTaXplKSA6IDA7XG4gICAgICBsZXQgYnJlYWtMb29wO1xuICAgICAgZm9yIChsZXQgaSA9IGFjdGl2ZUluZGV4ICsgMTsgaSA8IHNsaWRlcy5sZW5ndGg7IGkgKz0gMSkge1xuICAgICAgICBpZiAoc2xpZGVzW2ldICYmICFicmVha0xvb3ApIHtcbiAgICAgICAgICBzbGlkZVNpemUgKz0gTWF0aC5jZWlsKHNsaWRlc1tpXS5zd2lwZXJTbGlkZVNpemUpO1xuICAgICAgICAgIHNwdiArPSAxO1xuICAgICAgICAgIGlmIChzbGlkZVNpemUgPiBzd2lwZXJTaXplKSBicmVha0xvb3AgPSB0cnVlO1xuICAgICAgICB9XG4gICAgICB9XG4gICAgICBmb3IgKGxldCBpID0gYWN0aXZlSW5kZXggLSAxOyBpID49IDA7IGkgLT0gMSkge1xuICAgICAgICBpZiAoc2xpZGVzW2ldICYmICFicmVha0xvb3ApIHtcbiAgICAgICAgICBzbGlkZVNpemUgKz0gc2xpZGVzW2ldLnN3aXBlclNsaWRlU2l6ZTtcbiAgICAgICAgICBzcHYgKz0gMTtcbiAgICAgICAgICBpZiAoc2xpZGVTaXplID4gc3dpcGVyU2l6ZSkgYnJlYWtMb29wID0gdHJ1ZTtcbiAgICAgICAgfVxuICAgICAgfVxuICAgIH0gZWxzZSB7XG4gICAgICAvLyBlc2xpbnQtZGlzYWJsZS1uZXh0LWxpbmVcbiAgICAgIGlmICh2aWV3ID09PSAnY3VycmVudCcpIHtcbiAgICAgICAgZm9yIChsZXQgaSA9IGFjdGl2ZUluZGV4ICsgMTsgaSA8IHNsaWRlcy5sZW5ndGg7IGkgKz0gMSkge1xuICAgICAgICAgIGNvbnN0IHNsaWRlSW5WaWV3ID0gZXhhY3QgPyBzbGlkZXNHcmlkW2ldICsgc2xpZGVzU2l6ZXNHcmlkW2ldIC0gc2xpZGVzR3JpZFthY3RpdmVJbmRleF0gPCBzd2lwZXJTaXplIDogc2xpZGVzR3JpZFtpXSAtIHNsaWRlc0dyaWRbYWN0aXZlSW5kZXhdIDwgc3dpcGVyU2l6ZTtcbiAgICAgICAgICBpZiAoc2xpZGVJblZpZXcpIHtcbiAgICAgICAgICAgIHNwdiArPSAxO1xuICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgICAgfSBlbHNlIHtcbiAgICAgICAgLy8gcHJldmlvdXNcbiAgICAgICAgZm9yIChsZXQgaSA9IGFjdGl2ZUluZGV4IC0gMTsgaSA+PSAwOyBpIC09IDEpIHtcbiAgICAgICAgICBjb25zdCBzbGlkZUluVmlldyA9IHNsaWRlc0dyaWRbYWN0aXZlSW5kZXhdIC0gc2xpZGVzR3JpZFtpXSA8IHN3aXBlclNpemU7XG4gICAgICAgICAgaWYgKHNsaWRlSW5WaWV3KSB7XG4gICAgICAgICAgICBzcHYgKz0gMTtcbiAgICAgICAgICB9XG4gICAgICAgIH1cbiAgICAgIH1cbiAgICB9XG4gICAgcmV0dXJuIHNwdjtcbiAgfVxuICB1cGRhdGUoKSB7XG4gICAgY29uc3Qgc3dpcGVyID0gdGhpcztcbiAgICBpZiAoIXN3aXBlciB8fCBzd2lwZXIuZGVzdHJveWVkKSByZXR1cm47XG4gICAgY29uc3Qge1xuICAgICAgc25hcEdyaWQsXG4gICAgICBwYXJhbXNcbiAgICB9ID0gc3dpcGVyO1xuICAgIC8vIEJyZWFrcG9pbnRzXG4gICAgaWYgKHBhcmFtcy5icmVha3BvaW50cykge1xuICAgICAgc3dpcGVyLnNldEJyZWFrcG9pbnQoKTtcbiAgICB9XG4gICAgWy4uLnN3aXBlci5lbC5xdWVyeVNlbGVjdG9yQWxsKCdbbG9hZGluZz1cImxhenlcIl0nKV0uZm9yRWFjaChpbWFnZUVsID0+IHtcbiAgICAgIGlmIChpbWFnZUVsLmNvbXBsZXRlKSB7XG4gICAgICAgIHByb2Nlc3NMYXp5UHJlbG9hZGVyKHN3aXBlciwgaW1hZ2VFbCk7XG4gICAgICB9XG4gICAgfSk7XG4gICAgc3dpcGVyLnVwZGF0ZVNpemUoKTtcbiAgICBzd2lwZXIudXBkYXRlU2xpZGVzKCk7XG4gICAgc3dpcGVyLnVwZGF0ZVByb2dyZXNzKCk7XG4gICAgc3dpcGVyLnVwZGF0ZVNsaWRlc0NsYXNzZXMoKTtcbiAgICBmdW5jdGlvbiBzZXRUcmFuc2xhdGUoKSB7XG4gICAgICBjb25zdCB0cmFuc2xhdGVWYWx1ZSA9IHN3aXBlci5ydGxUcmFuc2xhdGUgPyBzd2lwZXIudHJhbnNsYXRlICogLTEgOiBzd2lwZXIudHJhbnNsYXRlO1xuICAgICAgY29uc3QgbmV3VHJhbnNsYXRlID0gTWF0aC5taW4oTWF0aC5tYXgodHJhbnNsYXRlVmFsdWUsIHN3aXBlci5tYXhUcmFuc2xhdGUoKSksIHN3aXBlci5taW5UcmFuc2xhdGUoKSk7XG4gICAgICBzd2lwZXIuc2V0VHJhbnNsYXRlKG5ld1RyYW5zbGF0ZSk7XG4gICAgICBzd2lwZXIudXBkYXRlQWN0aXZlSW5kZXgoKTtcbiAgICAgIHN3aXBlci51cGRhdGVTbGlkZXNDbGFzc2VzKCk7XG4gICAgfVxuICAgIGxldCB0cmFuc2xhdGVkO1xuICAgIGlmIChwYXJhbXMuZnJlZU1vZGUgJiYgcGFyYW1zLmZyZWVNb2RlLmVuYWJsZWQgJiYgIXBhcmFtcy5jc3NNb2RlKSB7XG4gICAgICBzZXRUcmFuc2xhdGUoKTtcbiAgICAgIGlmIChwYXJhbXMuYXV0b0hlaWdodCkge1xuICAgICAgICBzd2lwZXIudXBkYXRlQXV0b0hlaWdodCgpO1xuICAgICAgfVxuICAgIH0gZWxzZSB7XG4gICAgICBpZiAoKHBhcmFtcy5zbGlkZXNQZXJWaWV3ID09PSAnYXV0bycgfHwgcGFyYW1zLnNsaWRlc1BlclZpZXcgPiAxKSAmJiBzd2lwZXIuaXNFbmQgJiYgIXBhcmFtcy5jZW50ZXJlZFNsaWRlcykge1xuICAgICAgICBjb25zdCBzbGlkZXMgPSBzd2lwZXIudmlydHVhbCAmJiBwYXJhbXMudmlydHVhbC5lbmFibGVkID8gc3dpcGVyLnZpcnR1YWwuc2xpZGVzIDogc3dpcGVyLnNsaWRlcztcbiAgICAgICAgdHJhbnNsYXRlZCA9IHN3aXBlci5zbGlkZVRvKHNsaWRlcy5sZW5ndGggLSAxLCAwLCBmYWxzZSwgdHJ1ZSk7XG4gICAgICB9IGVsc2Uge1xuICAgICAgICB0cmFuc2xhdGVkID0gc3dpcGVyLnNsaWRlVG8oc3dpcGVyLmFjdGl2ZUluZGV4LCAwLCBmYWxzZSwgdHJ1ZSk7XG4gICAgICB9XG4gICAgICBpZiAoIXRyYW5zbGF0ZWQpIHtcbiAgICAgICAgc2V0VHJhbnNsYXRlKCk7XG4gICAgICB9XG4gICAgfVxuICAgIGlmIChwYXJhbXMud2F0Y2hPdmVyZmxvdyAmJiBzbmFwR3JpZCAhPT0gc3dpcGVyLnNuYXBHcmlkKSB7XG4gICAgICBzd2lwZXIuY2hlY2tPdmVyZmxvdygpO1xuICAgIH1cbiAgICBzd2lwZXIuZW1pdCgndXBkYXRlJyk7XG4gIH1cbiAgY2hhbmdlRGlyZWN0aW9uKG5ld0RpcmVjdGlvbiwgbmVlZFVwZGF0ZSkge1xuICAgIGlmIChuZWVkVXBkYXRlID09PSB2b2lkIDApIHtcbiAgICAgIG5lZWRVcGRhdGUgPSB0cnVlO1xuICAgIH1cbiAgICBjb25zdCBzd2lwZXIgPSB0aGlzO1xuICAgIGNvbnN0IGN1cnJlbnREaXJlY3Rpb24gPSBzd2lwZXIucGFyYW1zLmRpcmVjdGlvbjtcbiAgICBpZiAoIW5ld0RpcmVjdGlvbikge1xuICAgICAgLy8gZXNsaW50LWRpc2FibGUtbmV4dC1saW5lXG4gICAgICBuZXdEaXJlY3Rpb24gPSBjdXJyZW50RGlyZWN0aW9uID09PSAnaG9yaXpvbnRhbCcgPyAndmVydGljYWwnIDogJ2hvcml6b250YWwnO1xuICAgIH1cbiAgICBpZiAobmV3RGlyZWN0aW9uID09PSBjdXJyZW50RGlyZWN0aW9uIHx8IG5ld0RpcmVjdGlvbiAhPT0gJ2hvcml6b250YWwnICYmIG5ld0RpcmVjdGlvbiAhPT0gJ3ZlcnRpY2FsJykge1xuICAgICAgcmV0dXJuIHN3aXBlcjtcbiAgICB9XG4gICAgc3dpcGVyLmVsLmNsYXNzTGlzdC5yZW1vdmUoYCR7c3dpcGVyLnBhcmFtcy5jb250YWluZXJNb2RpZmllckNsYXNzfSR7Y3VycmVudERpcmVjdGlvbn1gKTtcbiAgICBzd2lwZXIuZWwuY2xhc3NMaXN0LmFkZChgJHtzd2lwZXIucGFyYW1zLmNvbnRhaW5lck1vZGlmaWVyQ2xhc3N9JHtuZXdEaXJlY3Rpb259YCk7XG4gICAgc3dpcGVyLmVtaXRDb250YWluZXJDbGFzc2VzKCk7XG4gICAgc3dpcGVyLnBhcmFtcy5kaXJlY3Rpb24gPSBuZXdEaXJlY3Rpb247XG4gICAgc3dpcGVyLnNsaWRlcy5mb3JFYWNoKHNsaWRlRWwgPT4ge1xuICAgICAgaWYgKG5ld0RpcmVjdGlvbiA9PT0gJ3ZlcnRpY2FsJykge1xuICAgICAgICBzbGlkZUVsLnN0eWxlLndpZHRoID0gJyc7XG4gICAgICB9IGVsc2Uge1xuICAgICAgICBzbGlkZUVsLnN0eWxlLmhlaWdodCA9ICcnO1xuICAgICAgfVxuICAgIH0pO1xuICAgIHN3aXBlci5lbWl0KCdjaGFuZ2VEaXJlY3Rpb24nKTtcbiAgICBpZiAobmVlZFVwZGF0ZSkgc3dpcGVyLnVwZGF0ZSgpO1xuICAgIHJldHVybiBzd2lwZXI7XG4gIH1cbiAgY2hhbmdlTGFuZ3VhZ2VEaXJlY3Rpb24oZGlyZWN0aW9uKSB7XG4gICAgY29uc3Qgc3dpcGVyID0gdGhpcztcbiAgICBpZiAoc3dpcGVyLnJ0bCAmJiBkaXJlY3Rpb24gPT09ICdydGwnIHx8ICFzd2lwZXIucnRsICYmIGRpcmVjdGlvbiA9PT0gJ2x0cicpIHJldHVybjtcbiAgICBzd2lwZXIucnRsID0gZGlyZWN0aW9uID09PSAncnRsJztcbiAgICBzd2lwZXIucnRsVHJhbnNsYXRlID0gc3dpcGVyLnBhcmFtcy5kaXJlY3Rpb24gPT09ICdob3Jpem9udGFsJyAmJiBzd2lwZXIucnRsO1xuICAgIGlmIChzd2lwZXIucnRsKSB7XG4gICAgICBzd2lwZXIuZWwuY2xhc3NMaXN0LmFkZChgJHtzd2lwZXIucGFyYW1zLmNvbnRhaW5lck1vZGlmaWVyQ2xhc3N9cnRsYCk7XG4gICAgICBzd2lwZXIuZWwuZGlyID0gJ3J0bCc7XG4gICAgfSBlbHNlIHtcbiAgICAgIHN3aXBlci5lbC5jbGFzc0xpc3QucmVtb3ZlKGAke3N3aXBlci5wYXJhbXMuY29udGFpbmVyTW9kaWZpZXJDbGFzc31ydGxgKTtcbiAgICAgIHN3aXBlci5lbC5kaXIgPSAnbHRyJztcbiAgICB9XG4gICAgc3dpcGVyLnVwZGF0ZSgpO1xuICB9XG4gIG1vdW50KGVsZW1lbnQpIHtcbiAgICBjb25zdCBzd2lwZXIgPSB0aGlzO1xuICAgIGlmIChzd2lwZXIubW91bnRlZCkgcmV0dXJuIHRydWU7XG5cbiAgICAvLyBGaW5kIGVsXG4gICAgbGV0IGVsID0gZWxlbWVudCB8fCBzd2lwZXIucGFyYW1zLmVsO1xuICAgIGlmICh0eXBlb2YgZWwgPT09ICdzdHJpbmcnKSB7XG4gICAgICBlbCA9IGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3IoZWwpO1xuICAgIH1cbiAgICBpZiAoIWVsKSB7XG4gICAgICByZXR1cm4gZmFsc2U7XG4gICAgfVxuICAgIGVsLnN3aXBlciA9IHN3aXBlcjtcbiAgICBpZiAoZWwucGFyZW50Tm9kZSAmJiBlbC5wYXJlbnROb2RlLmhvc3QgJiYgZWwucGFyZW50Tm9kZS5ob3N0Lm5vZGVOYW1lID09PSBzd2lwZXIucGFyYW1zLnN3aXBlckVsZW1lbnROb2RlTmFtZS50b1VwcGVyQ2FzZSgpKSB7XG4gICAgICBzd2lwZXIuaXNFbGVtZW50ID0gdHJ1ZTtcbiAgICB9XG4gICAgY29uc3QgZ2V0V3JhcHBlclNlbGVjdG9yID0gKCkgPT4ge1xuICAgICAgcmV0dXJuIGAuJHsoc3dpcGVyLnBhcmFtcy53cmFwcGVyQ2xhc3MgfHwgJycpLnRyaW0oKS5zcGxpdCgnICcpLmpvaW4oJy4nKX1gO1xuICAgIH07XG4gICAgY29uc3QgZ2V0V3JhcHBlciA9ICgpID0+IHtcbiAgICAgIGlmIChlbCAmJiBlbC5zaGFkb3dSb290ICYmIGVsLnNoYWRvd1Jvb3QucXVlcnlTZWxlY3Rvcikge1xuICAgICAgICBjb25zdCByZXMgPSBlbC5zaGFkb3dSb290LnF1ZXJ5U2VsZWN0b3IoZ2V0V3JhcHBlclNlbGVjdG9yKCkpO1xuICAgICAgICAvLyBDaGlsZHJlbiBuZWVkcyB0byByZXR1cm4gc2xvdCBpdGVtc1xuICAgICAgICByZXR1cm4gcmVzO1xuICAgICAgfVxuICAgICAgcmV0dXJuIGVsZW1lbnRDaGlsZHJlbihlbCwgZ2V0V3JhcHBlclNlbGVjdG9yKCkpWzBdO1xuICAgIH07XG4gICAgLy8gRmluZCBXcmFwcGVyXG4gICAgbGV0IHdyYXBwZXJFbCA9IGdldFdyYXBwZXIoKTtcbiAgICBpZiAoIXdyYXBwZXJFbCAmJiBzd2lwZXIucGFyYW1zLmNyZWF0ZUVsZW1lbnRzKSB7XG4gICAgICB3cmFwcGVyRWwgPSBjcmVhdGVFbGVtZW50KCdkaXYnLCBzd2lwZXIucGFyYW1zLndyYXBwZXJDbGFzcyk7XG4gICAgICBlbC5hcHBlbmQod3JhcHBlckVsKTtcbiAgICAgIGVsZW1lbnRDaGlsZHJlbihlbCwgYC4ke3N3aXBlci5wYXJhbXMuc2xpZGVDbGFzc31gKS5mb3JFYWNoKHNsaWRlRWwgPT4ge1xuICAgICAgICB3cmFwcGVyRWwuYXBwZW5kKHNsaWRlRWwpO1xuICAgICAgfSk7XG4gICAgfVxuICAgIE9iamVjdC5hc3NpZ24oc3dpcGVyLCB7XG4gICAgICBlbCxcbiAgICAgIHdyYXBwZXJFbCxcbiAgICAgIHNsaWRlc0VsOiBzd2lwZXIuaXNFbGVtZW50ICYmICFlbC5wYXJlbnROb2RlLmhvc3Quc2xpZGVTbG90cyA/IGVsLnBhcmVudE5vZGUuaG9zdCA6IHdyYXBwZXJFbCxcbiAgICAgIGhvc3RFbDogc3dpcGVyLmlzRWxlbWVudCA/IGVsLnBhcmVudE5vZGUuaG9zdCA6IGVsLFxuICAgICAgbW91bnRlZDogdHJ1ZSxcbiAgICAgIC8vIFJUTFxuICAgICAgcnRsOiBlbC5kaXIudG9Mb3dlckNhc2UoKSA9PT0gJ3J0bCcgfHwgZWxlbWVudFN0eWxlKGVsLCAnZGlyZWN0aW9uJykgPT09ICdydGwnLFxuICAgICAgcnRsVHJhbnNsYXRlOiBzd2lwZXIucGFyYW1zLmRpcmVjdGlvbiA9PT0gJ2hvcml6b250YWwnICYmIChlbC5kaXIudG9Mb3dlckNhc2UoKSA9PT0gJ3J0bCcgfHwgZWxlbWVudFN0eWxlKGVsLCAnZGlyZWN0aW9uJykgPT09ICdydGwnKSxcbiAgICAgIHdyb25nUlRMOiBlbGVtZW50U3R5bGUod3JhcHBlckVsLCAnZGlzcGxheScpID09PSAnLXdlYmtpdC1ib3gnXG4gICAgfSk7XG4gICAgcmV0dXJuIHRydWU7XG4gIH1cbiAgaW5pdChlbCkge1xuICAgIGNvbnN0IHN3aXBlciA9IHRoaXM7XG4gICAgaWYgKHN3aXBlci5pbml0aWFsaXplZCkgcmV0dXJuIHN3aXBlcjtcbiAgICBjb25zdCBtb3VudGVkID0gc3dpcGVyLm1vdW50KGVsKTtcbiAgICBpZiAobW91bnRlZCA9PT0gZmFsc2UpIHJldHVybiBzd2lwZXI7XG4gICAgc3dpcGVyLmVtaXQoJ2JlZm9yZUluaXQnKTtcblxuICAgIC8vIFNldCBicmVha3BvaW50XG4gICAgaWYgKHN3aXBlci5wYXJhbXMuYnJlYWtwb2ludHMpIHtcbiAgICAgIHN3aXBlci5zZXRCcmVha3BvaW50KCk7XG4gICAgfVxuXG4gICAgLy8gQWRkIENsYXNzZXNcbiAgICBzd2lwZXIuYWRkQ2xhc3NlcygpO1xuXG4gICAgLy8gVXBkYXRlIHNpemVcbiAgICBzd2lwZXIudXBkYXRlU2l6ZSgpO1xuXG4gICAgLy8gVXBkYXRlIHNsaWRlc1xuICAgIHN3aXBlci51cGRhdGVTbGlkZXMoKTtcbiAgICBpZiAoc3dpcGVyLnBhcmFtcy53YXRjaE92ZXJmbG93KSB7XG4gICAgICBzd2lwZXIuY2hlY2tPdmVyZmxvdygpO1xuICAgIH1cblxuICAgIC8vIFNldCBHcmFiIEN1cnNvclxuICAgIGlmIChzd2lwZXIucGFyYW1zLmdyYWJDdXJzb3IgJiYgc3dpcGVyLmVuYWJsZWQpIHtcbiAgICAgIHN3aXBlci5zZXRHcmFiQ3Vyc29yKCk7XG4gICAgfVxuXG4gICAgLy8gU2xpZGUgVG8gSW5pdGlhbCBTbGlkZVxuICAgIGlmIChzd2lwZXIucGFyYW1zLmxvb3AgJiYgc3dpcGVyLnZpcnR1YWwgJiYgc3dpcGVyLnBhcmFtcy52aXJ0dWFsLmVuYWJsZWQpIHtcbiAgICAgIHN3aXBlci5zbGlkZVRvKHN3aXBlci5wYXJhbXMuaW5pdGlhbFNsaWRlICsgc3dpcGVyLnZpcnR1YWwuc2xpZGVzQmVmb3JlLCAwLCBzd2lwZXIucGFyYW1zLnJ1bkNhbGxiYWNrc09uSW5pdCwgZmFsc2UsIHRydWUpO1xuICAgIH0gZWxzZSB7XG4gICAgICBzd2lwZXIuc2xpZGVUbyhzd2lwZXIucGFyYW1zLmluaXRpYWxTbGlkZSwgMCwgc3dpcGVyLnBhcmFtcy5ydW5DYWxsYmFja3NPbkluaXQsIGZhbHNlLCB0cnVlKTtcbiAgICB9XG5cbiAgICAvLyBDcmVhdGUgbG9vcFxuICAgIGlmIChzd2lwZXIucGFyYW1zLmxvb3ApIHtcbiAgICAgIHN3aXBlci5sb29wQ3JlYXRlKCk7XG4gICAgfVxuXG4gICAgLy8gQXR0YWNoIGV2ZW50c1xuICAgIHN3aXBlci5hdHRhY2hFdmVudHMoKTtcbiAgICBjb25zdCBsYXp5RWxlbWVudHMgPSBbLi4uc3dpcGVyLmVsLnF1ZXJ5U2VsZWN0b3JBbGwoJ1tsb2FkaW5nPVwibGF6eVwiXScpXTtcbiAgICBpZiAoc3dpcGVyLmlzRWxlbWVudCkge1xuICAgICAgbGF6eUVsZW1lbnRzLnB1c2goLi4uc3dpcGVyLmhvc3RFbC5xdWVyeVNlbGVjdG9yQWxsKCdbbG9hZGluZz1cImxhenlcIl0nKSk7XG4gICAgfVxuICAgIGxhenlFbGVtZW50cy5mb3JFYWNoKGltYWdlRWwgPT4ge1xuICAgICAgaWYgKGltYWdlRWwuY29tcGxldGUpIHtcbiAgICAgICAgcHJvY2Vzc0xhenlQcmVsb2FkZXIoc3dpcGVyLCBpbWFnZUVsKTtcbiAgICAgIH0gZWxzZSB7XG4gICAgICAgIGltYWdlRWwuYWRkRXZlbnRMaXN0ZW5lcignbG9hZCcsIGUgPT4ge1xuICAgICAgICAgIHByb2Nlc3NMYXp5UHJlbG9hZGVyKHN3aXBlciwgZS50YXJnZXQpO1xuICAgICAgICB9KTtcbiAgICAgIH1cbiAgICB9KTtcbiAgICBwcmVsb2FkKHN3aXBlcik7XG5cbiAgICAvLyBJbml0IEZsYWdcbiAgICBzd2lwZXIuaW5pdGlhbGl6ZWQgPSB0cnVlO1xuICAgIHByZWxvYWQoc3dpcGVyKTtcblxuICAgIC8vIEVtaXRcbiAgICBzd2lwZXIuZW1pdCgnaW5pdCcpO1xuICAgIHN3aXBlci5lbWl0KCdhZnRlckluaXQnKTtcbiAgICByZXR1cm4gc3dpcGVyO1xuICB9XG4gIGRlc3Ryb3koZGVsZXRlSW5zdGFuY2UsIGNsZWFuU3R5bGVzKSB7XG4gICAgaWYgKGRlbGV0ZUluc3RhbmNlID09PSB2b2lkIDApIHtcbiAgICAgIGRlbGV0ZUluc3RhbmNlID0gdHJ1ZTtcbiAgICB9XG4gICAgaWYgKGNsZWFuU3R5bGVzID09PSB2b2lkIDApIHtcbiAgICAgIGNsZWFuU3R5bGVzID0gdHJ1ZTtcbiAgICB9XG4gICAgY29uc3Qgc3dpcGVyID0gdGhpcztcbiAgICBjb25zdCB7XG4gICAgICBwYXJhbXMsXG4gICAgICBlbCxcbiAgICAgIHdyYXBwZXJFbCxcbiAgICAgIHNsaWRlc1xuICAgIH0gPSBzd2lwZXI7XG4gICAgaWYgKHR5cGVvZiBzd2lwZXIucGFyYW1zID09PSAndW5kZWZpbmVkJyB8fCBzd2lwZXIuZGVzdHJveWVkKSB7XG4gICAgICByZXR1cm4gbnVsbDtcbiAgICB9XG4gICAgc3dpcGVyLmVtaXQoJ2JlZm9yZURlc3Ryb3knKTtcblxuICAgIC8vIEluaXQgRmxhZ1xuICAgIHN3aXBlci5pbml0aWFsaXplZCA9IGZhbHNlO1xuXG4gICAgLy8gRGV0YWNoIGV2ZW50c1xuICAgIHN3aXBlci5kZXRhY2hFdmVudHMoKTtcblxuICAgIC8vIERlc3Ryb3kgbG9vcFxuICAgIGlmIChwYXJhbXMubG9vcCkge1xuICAgICAgc3dpcGVyLmxvb3BEZXN0cm95KCk7XG4gICAgfVxuXG4gICAgLy8gQ2xlYW51cCBzdHlsZXNcbiAgICBpZiAoY2xlYW5TdHlsZXMpIHtcbiAgICAgIHN3aXBlci5yZW1vdmVDbGFzc2VzKCk7XG4gICAgICBpZiAoZWwgJiYgdHlwZW9mIGVsICE9PSAnc3RyaW5nJykge1xuICAgICAgICBlbC5yZW1vdmVBdHRyaWJ1dGUoJ3N0eWxlJyk7XG4gICAgICB9XG4gICAgICBpZiAod3JhcHBlckVsKSB7XG4gICAgICAgIHdyYXBwZXJFbC5yZW1vdmVBdHRyaWJ1dGUoJ3N0eWxlJyk7XG4gICAgICB9XG4gICAgICBpZiAoc2xpZGVzICYmIHNsaWRlcy5sZW5ndGgpIHtcbiAgICAgICAgc2xpZGVzLmZvckVhY2goc2xpZGVFbCA9PiB7XG4gICAgICAgICAgc2xpZGVFbC5jbGFzc0xpc3QucmVtb3ZlKHBhcmFtcy5zbGlkZVZpc2libGVDbGFzcywgcGFyYW1zLnNsaWRlRnVsbHlWaXNpYmxlQ2xhc3MsIHBhcmFtcy5zbGlkZUFjdGl2ZUNsYXNzLCBwYXJhbXMuc2xpZGVOZXh0Q2xhc3MsIHBhcmFtcy5zbGlkZVByZXZDbGFzcyk7XG4gICAgICAgICAgc2xpZGVFbC5yZW1vdmVBdHRyaWJ1dGUoJ3N0eWxlJyk7XG4gICAgICAgICAgc2xpZGVFbC5yZW1vdmVBdHRyaWJ1dGUoJ2RhdGEtc3dpcGVyLXNsaWRlLWluZGV4Jyk7XG4gICAgICAgIH0pO1xuICAgICAgfVxuICAgIH1cbiAgICBzd2lwZXIuZW1pdCgnZGVzdHJveScpO1xuXG4gICAgLy8gRGV0YWNoIGVtaXR0ZXIgZXZlbnRzXG4gICAgT2JqZWN0LmtleXMoc3dpcGVyLmV2ZW50c0xpc3RlbmVycykuZm9yRWFjaChldmVudE5hbWUgPT4ge1xuICAgICAgc3dpcGVyLm9mZihldmVudE5hbWUpO1xuICAgIH0pO1xuICAgIGlmIChkZWxldGVJbnN0YW5jZSAhPT0gZmFsc2UpIHtcbiAgICAgIGlmIChzd2lwZXIuZWwgJiYgdHlwZW9mIHN3aXBlci5lbCAhPT0gJ3N0cmluZycpIHtcbiAgICAgICAgc3dpcGVyLmVsLnN3aXBlciA9IG51bGw7XG4gICAgICB9XG4gICAgICBkZWxldGVQcm9wcyhzd2lwZXIpO1xuICAgIH1cbiAgICBzd2lwZXIuZGVzdHJveWVkID0gdHJ1ZTtcbiAgICByZXR1cm4gbnVsbDtcbiAgfVxuICBzdGF0aWMgZXh0ZW5kRGVmYXVsdHMobmV3RGVmYXVsdHMpIHtcbiAgICBleHRlbmQoZXh0ZW5kZWREZWZhdWx0cywgbmV3RGVmYXVsdHMpO1xuICB9XG4gIHN0YXRpYyBnZXQgZXh0ZW5kZWREZWZhdWx0cygpIHtcbiAgICByZXR1cm4gZXh0ZW5kZWREZWZhdWx0cztcbiAgfVxuICBzdGF0aWMgZ2V0IGRlZmF1bHRzKCkge1xuICAgIHJldHVybiBkZWZhdWx0cztcbiAgfVxuICBzdGF0aWMgaW5zdGFsbE1vZHVsZShtb2QpIHtcbiAgICBpZiAoIVN3aXBlci5wcm90b3R5cGUuX19tb2R1bGVzX18pIFN3aXBlci5wcm90b3R5cGUuX19tb2R1bGVzX18gPSBbXTtcbiAgICBjb25zdCBtb2R1bGVzID0gU3dpcGVyLnByb3RvdHlwZS5fX21vZHVsZXNfXztcbiAgICBpZiAodHlwZW9mIG1vZCA9PT0gJ2Z1bmN0aW9uJyAmJiBtb2R1bGVzLmluZGV4T2YobW9kKSA8IDApIHtcbiAgICAgIG1vZHVsZXMucHVzaChtb2QpO1xuICAgIH1cbiAgfVxuICBzdGF0aWMgdXNlKG1vZHVsZSkge1xuICAgIGlmIChBcnJheS5pc0FycmF5KG1vZHVsZSkpIHtcbiAgICAgIG1vZHVsZS5mb3JFYWNoKG0gPT4gU3dpcGVyLmluc3RhbGxNb2R1bGUobSkpO1xuICAgICAgcmV0dXJuIFN3aXBlcjtcbiAgICB9XG4gICAgU3dpcGVyLmluc3RhbGxNb2R1bGUobW9kdWxlKTtcbiAgICByZXR1cm4gU3dpcGVyO1xuICB9XG59XG5PYmplY3Qua2V5cyhwcm90b3R5cGVzKS5mb3JFYWNoKHByb3RvdHlwZUdyb3VwID0+IHtcbiAgT2JqZWN0LmtleXMocHJvdG90eXBlc1twcm90b3R5cGVHcm91cF0pLmZvckVhY2gocHJvdG9NZXRob2QgPT4ge1xuICAgIFN3aXBlci5wcm90b3R5cGVbcHJvdG9NZXRob2RdID0gcHJvdG90eXBlc1twcm90b3R5cGVHcm91cF1bcHJvdG9NZXRob2RdO1xuICB9KTtcbn0pO1xuU3dpcGVyLnVzZShbUmVzaXplLCBPYnNlcnZlcl0pO1xuXG5leHBvcnQgeyBTd2lwZXIgYXMgUywgZGVmYXVsdHMgYXMgZCB9O1xuIiwgImltcG9ydCB7IGUgYXMgZWxlbWVudENoaWxkcmVuLCBjIGFzIGNyZWF0ZUVsZW1lbnQgfSBmcm9tICcuL3V0aWxzLm1qcyc7XG5cbmZ1bmN0aW9uIGNyZWF0ZUVsZW1lbnRJZk5vdERlZmluZWQoc3dpcGVyLCBvcmlnaW5hbFBhcmFtcywgcGFyYW1zLCBjaGVja1Byb3BzKSB7XG4gIGlmIChzd2lwZXIucGFyYW1zLmNyZWF0ZUVsZW1lbnRzKSB7XG4gICAgT2JqZWN0LmtleXMoY2hlY2tQcm9wcykuZm9yRWFjaChrZXkgPT4ge1xuICAgICAgaWYgKCFwYXJhbXNba2V5XSAmJiBwYXJhbXMuYXV0byA9PT0gdHJ1ZSkge1xuICAgICAgICBsZXQgZWxlbWVudCA9IGVsZW1lbnRDaGlsZHJlbihzd2lwZXIuZWwsIGAuJHtjaGVja1Byb3BzW2tleV19YClbMF07XG4gICAgICAgIGlmICghZWxlbWVudCkge1xuICAgICAgICAgIGVsZW1lbnQgPSBjcmVhdGVFbGVtZW50KCdkaXYnLCBjaGVja1Byb3BzW2tleV0pO1xuICAgICAgICAgIGVsZW1lbnQuY2xhc3NOYW1lID0gY2hlY2tQcm9wc1trZXldO1xuICAgICAgICAgIHN3aXBlci5lbC5hcHBlbmQoZWxlbWVudCk7XG4gICAgICAgIH1cbiAgICAgICAgcGFyYW1zW2tleV0gPSBlbGVtZW50O1xuICAgICAgICBvcmlnaW5hbFBhcmFtc1trZXldID0gZWxlbWVudDtcbiAgICAgIH1cbiAgICB9KTtcbiAgfVxuICByZXR1cm4gcGFyYW1zO1xufVxuXG5leHBvcnQgeyBjcmVhdGVFbGVtZW50SWZOb3REZWZpbmVkIGFzIGMgfTtcbiIsICJpbXBvcnQgeyBjIGFzIGNyZWF0ZUVsZW1lbnRJZk5vdERlZmluZWQgfSBmcm9tICcuLi9zaGFyZWQvY3JlYXRlLWVsZW1lbnQtaWYtbm90LWRlZmluZWQubWpzJztcbmltcG9ydCB7IG0gYXMgbWFrZUVsZW1lbnRzQXJyYXkgfSBmcm9tICcuLi9zaGFyZWQvdXRpbHMubWpzJztcblxuZnVuY3Rpb24gTmF2aWdhdGlvbihfcmVmKSB7XG4gIGxldCB7XG4gICAgc3dpcGVyLFxuICAgIGV4dGVuZFBhcmFtcyxcbiAgICBvbixcbiAgICBlbWl0XG4gIH0gPSBfcmVmO1xuICBleHRlbmRQYXJhbXMoe1xuICAgIG5hdmlnYXRpb246IHtcbiAgICAgIG5leHRFbDogbnVsbCxcbiAgICAgIHByZXZFbDogbnVsbCxcbiAgICAgIGhpZGVPbkNsaWNrOiBmYWxzZSxcbiAgICAgIGRpc2FibGVkQ2xhc3M6ICdzd2lwZXItYnV0dG9uLWRpc2FibGVkJyxcbiAgICAgIGhpZGRlbkNsYXNzOiAnc3dpcGVyLWJ1dHRvbi1oaWRkZW4nLFxuICAgICAgbG9ja0NsYXNzOiAnc3dpcGVyLWJ1dHRvbi1sb2NrJyxcbiAgICAgIG5hdmlnYXRpb25EaXNhYmxlZENsYXNzOiAnc3dpcGVyLW5hdmlnYXRpb24tZGlzYWJsZWQnXG4gICAgfVxuICB9KTtcbiAgc3dpcGVyLm5hdmlnYXRpb24gPSB7XG4gICAgbmV4dEVsOiBudWxsLFxuICAgIHByZXZFbDogbnVsbFxuICB9O1xuICBmdW5jdGlvbiBnZXRFbChlbCkge1xuICAgIGxldCByZXM7XG4gICAgaWYgKGVsICYmIHR5cGVvZiBlbCA9PT0gJ3N0cmluZycgJiYgc3dpcGVyLmlzRWxlbWVudCkge1xuICAgICAgcmVzID0gc3dpcGVyLmVsLnF1ZXJ5U2VsZWN0b3IoZWwpIHx8IHN3aXBlci5ob3N0RWwucXVlcnlTZWxlY3RvcihlbCk7XG4gICAgICBpZiAocmVzKSByZXR1cm4gcmVzO1xuICAgIH1cbiAgICBpZiAoZWwpIHtcbiAgICAgIGlmICh0eXBlb2YgZWwgPT09ICdzdHJpbmcnKSByZXMgPSBbLi4uZG9jdW1lbnQucXVlcnlTZWxlY3RvckFsbChlbCldO1xuICAgICAgaWYgKHN3aXBlci5wYXJhbXMudW5pcXVlTmF2RWxlbWVudHMgJiYgdHlwZW9mIGVsID09PSAnc3RyaW5nJyAmJiByZXMgJiYgcmVzLmxlbmd0aCA+IDEgJiYgc3dpcGVyLmVsLnF1ZXJ5U2VsZWN0b3JBbGwoZWwpLmxlbmd0aCA9PT0gMSkge1xuICAgICAgICByZXMgPSBzd2lwZXIuZWwucXVlcnlTZWxlY3RvcihlbCk7XG4gICAgICB9IGVsc2UgaWYgKHJlcyAmJiByZXMubGVuZ3RoID09PSAxKSB7XG4gICAgICAgIHJlcyA9IHJlc1swXTtcbiAgICAgIH1cbiAgICB9XG4gICAgaWYgKGVsICYmICFyZXMpIHJldHVybiBlbDtcbiAgICAvLyBpZiAoQXJyYXkuaXNBcnJheShyZXMpICYmIHJlcy5sZW5ndGggPT09IDEpIHJlcyA9IHJlc1swXTtcbiAgICByZXR1cm4gcmVzO1xuICB9XG4gIGZ1bmN0aW9uIHRvZ2dsZUVsKGVsLCBkaXNhYmxlZCkge1xuICAgIGNvbnN0IHBhcmFtcyA9IHN3aXBlci5wYXJhbXMubmF2aWdhdGlvbjtcbiAgICBlbCA9IG1ha2VFbGVtZW50c0FycmF5KGVsKTtcbiAgICBlbC5mb3JFYWNoKHN1YkVsID0+IHtcbiAgICAgIGlmIChzdWJFbCkge1xuICAgICAgICBzdWJFbC5jbGFzc0xpc3RbZGlzYWJsZWQgPyAnYWRkJyA6ICdyZW1vdmUnXSguLi5wYXJhbXMuZGlzYWJsZWRDbGFzcy5zcGxpdCgnICcpKTtcbiAgICAgICAgaWYgKHN1YkVsLnRhZ05hbWUgPT09ICdCVVRUT04nKSBzdWJFbC5kaXNhYmxlZCA9IGRpc2FibGVkO1xuICAgICAgICBpZiAoc3dpcGVyLnBhcmFtcy53YXRjaE92ZXJmbG93ICYmIHN3aXBlci5lbmFibGVkKSB7XG4gICAgICAgICAgc3ViRWwuY2xhc3NMaXN0W3N3aXBlci5pc0xvY2tlZCA/ICdhZGQnIDogJ3JlbW92ZSddKHBhcmFtcy5sb2NrQ2xhc3MpO1xuICAgICAgICB9XG4gICAgICB9XG4gICAgfSk7XG4gIH1cbiAgZnVuY3Rpb24gdXBkYXRlKCkge1xuICAgIC8vIFVwZGF0ZSBOYXZpZ2F0aW9uIEJ1dHRvbnNcbiAgICBjb25zdCB7XG4gICAgICBuZXh0RWwsXG4gICAgICBwcmV2RWxcbiAgICB9ID0gc3dpcGVyLm5hdmlnYXRpb247XG4gICAgaWYgKHN3aXBlci5wYXJhbXMubG9vcCkge1xuICAgICAgdG9nZ2xlRWwocHJldkVsLCBmYWxzZSk7XG4gICAgICB0b2dnbGVFbChuZXh0RWwsIGZhbHNlKTtcbiAgICAgIHJldHVybjtcbiAgICB9XG4gICAgdG9nZ2xlRWwocHJldkVsLCBzd2lwZXIuaXNCZWdpbm5pbmcgJiYgIXN3aXBlci5wYXJhbXMucmV3aW5kKTtcbiAgICB0b2dnbGVFbChuZXh0RWwsIHN3aXBlci5pc0VuZCAmJiAhc3dpcGVyLnBhcmFtcy5yZXdpbmQpO1xuICB9XG4gIGZ1bmN0aW9uIG9uUHJldkNsaWNrKGUpIHtcbiAgICBlLnByZXZlbnREZWZhdWx0KCk7XG4gICAgaWYgKHN3aXBlci5pc0JlZ2lubmluZyAmJiAhc3dpcGVyLnBhcmFtcy5sb29wICYmICFzd2lwZXIucGFyYW1zLnJld2luZCkgcmV0dXJuO1xuICAgIHN3aXBlci5zbGlkZVByZXYoKTtcbiAgICBlbWl0KCduYXZpZ2F0aW9uUHJldicpO1xuICB9XG4gIGZ1bmN0aW9uIG9uTmV4dENsaWNrKGUpIHtcbiAgICBlLnByZXZlbnREZWZhdWx0KCk7XG4gICAgaWYgKHN3aXBlci5pc0VuZCAmJiAhc3dpcGVyLnBhcmFtcy5sb29wICYmICFzd2lwZXIucGFyYW1zLnJld2luZCkgcmV0dXJuO1xuICAgIHN3aXBlci5zbGlkZU5leHQoKTtcbiAgICBlbWl0KCduYXZpZ2F0aW9uTmV4dCcpO1xuICB9XG4gIGZ1bmN0aW9uIGluaXQoKSB7XG4gICAgY29uc3QgcGFyYW1zID0gc3dpcGVyLnBhcmFtcy5uYXZpZ2F0aW9uO1xuICAgIHN3aXBlci5wYXJhbXMubmF2aWdhdGlvbiA9IGNyZWF0ZUVsZW1lbnRJZk5vdERlZmluZWQoc3dpcGVyLCBzd2lwZXIub3JpZ2luYWxQYXJhbXMubmF2aWdhdGlvbiwgc3dpcGVyLnBhcmFtcy5uYXZpZ2F0aW9uLCB7XG4gICAgICBuZXh0RWw6ICdzd2lwZXItYnV0dG9uLW5leHQnLFxuICAgICAgcHJldkVsOiAnc3dpcGVyLWJ1dHRvbi1wcmV2J1xuICAgIH0pO1xuICAgIGlmICghKHBhcmFtcy5uZXh0RWwgfHwgcGFyYW1zLnByZXZFbCkpIHJldHVybjtcbiAgICBsZXQgbmV4dEVsID0gZ2V0RWwocGFyYW1zLm5leHRFbCk7XG4gICAgbGV0IHByZXZFbCA9IGdldEVsKHBhcmFtcy5wcmV2RWwpO1xuICAgIE9iamVjdC5hc3NpZ24oc3dpcGVyLm5hdmlnYXRpb24sIHtcbiAgICAgIG5leHRFbCxcbiAgICAgIHByZXZFbFxuICAgIH0pO1xuICAgIG5leHRFbCA9IG1ha2VFbGVtZW50c0FycmF5KG5leHRFbCk7XG4gICAgcHJldkVsID0gbWFrZUVsZW1lbnRzQXJyYXkocHJldkVsKTtcbiAgICBjb25zdCBpbml0QnV0dG9uID0gKGVsLCBkaXIpID0+IHtcbiAgICAgIGlmIChlbCkge1xuICAgICAgICBlbC5hZGRFdmVudExpc3RlbmVyKCdjbGljaycsIGRpciA9PT0gJ25leHQnID8gb25OZXh0Q2xpY2sgOiBvblByZXZDbGljayk7XG4gICAgICB9XG4gICAgICBpZiAoIXN3aXBlci5lbmFibGVkICYmIGVsKSB7XG4gICAgICAgIGVsLmNsYXNzTGlzdC5hZGQoLi4ucGFyYW1zLmxvY2tDbGFzcy5zcGxpdCgnICcpKTtcbiAgICAgIH1cbiAgICB9O1xuICAgIG5leHRFbC5mb3JFYWNoKGVsID0+IGluaXRCdXR0b24oZWwsICduZXh0JykpO1xuICAgIHByZXZFbC5mb3JFYWNoKGVsID0+IGluaXRCdXR0b24oZWwsICdwcmV2JykpO1xuICB9XG4gIGZ1bmN0aW9uIGRlc3Ryb3koKSB7XG4gICAgbGV0IHtcbiAgICAgIG5leHRFbCxcbiAgICAgIHByZXZFbFxuICAgIH0gPSBzd2lwZXIubmF2aWdhdGlvbjtcbiAgICBuZXh0RWwgPSBtYWtlRWxlbWVudHNBcnJheShuZXh0RWwpO1xuICAgIHByZXZFbCA9IG1ha2VFbGVtZW50c0FycmF5KHByZXZFbCk7XG4gICAgY29uc3QgZGVzdHJveUJ1dHRvbiA9IChlbCwgZGlyKSA9PiB7XG4gICAgICBlbC5yZW1vdmVFdmVudExpc3RlbmVyKCdjbGljaycsIGRpciA9PT0gJ25leHQnID8gb25OZXh0Q2xpY2sgOiBvblByZXZDbGljayk7XG4gICAgICBlbC5jbGFzc0xpc3QucmVtb3ZlKC4uLnN3aXBlci5wYXJhbXMubmF2aWdhdGlvbi5kaXNhYmxlZENsYXNzLnNwbGl0KCcgJykpO1xuICAgIH07XG4gICAgbmV4dEVsLmZvckVhY2goZWwgPT4gZGVzdHJveUJ1dHRvbihlbCwgJ25leHQnKSk7XG4gICAgcHJldkVsLmZvckVhY2goZWwgPT4gZGVzdHJveUJ1dHRvbihlbCwgJ3ByZXYnKSk7XG4gIH1cbiAgb24oJ2luaXQnLCAoKSA9PiB7XG4gICAgaWYgKHN3aXBlci5wYXJhbXMubmF2aWdhdGlvbi5lbmFibGVkID09PSBmYWxzZSkge1xuICAgICAgLy8gZXNsaW50LWRpc2FibGUtbmV4dC1saW5lXG4gICAgICBkaXNhYmxlKCk7XG4gICAgfSBlbHNlIHtcbiAgICAgIGluaXQoKTtcbiAgICAgIHVwZGF0ZSgpO1xuICAgIH1cbiAgfSk7XG4gIG9uKCd0b0VkZ2UgZnJvbUVkZ2UgbG9jayB1bmxvY2snLCAoKSA9PiB7XG4gICAgdXBkYXRlKCk7XG4gIH0pO1xuICBvbignZGVzdHJveScsICgpID0+IHtcbiAgICBkZXN0cm95KCk7XG4gIH0pO1xuICBvbignZW5hYmxlIGRpc2FibGUnLCAoKSA9PiB7XG4gICAgbGV0IHtcbiAgICAgIG5leHRFbCxcbiAgICAgIHByZXZFbFxuICAgIH0gPSBzd2lwZXIubmF2aWdhdGlvbjtcbiAgICBuZXh0RWwgPSBtYWtlRWxlbWVudHNBcnJheShuZXh0RWwpO1xuICAgIHByZXZFbCA9IG1ha2VFbGVtZW50c0FycmF5KHByZXZFbCk7XG4gICAgaWYgKHN3aXBlci5lbmFibGVkKSB7XG4gICAgICB1cGRhdGUoKTtcbiAgICAgIHJldHVybjtcbiAgICB9XG4gICAgWy4uLm5leHRFbCwgLi4ucHJldkVsXS5maWx0ZXIoZWwgPT4gISFlbCkuZm9yRWFjaChlbCA9PiBlbC5jbGFzc0xpc3QuYWRkKHN3aXBlci5wYXJhbXMubmF2aWdhdGlvbi5sb2NrQ2xhc3MpKTtcbiAgfSk7XG4gIG9uKCdjbGljaycsIChfcywgZSkgPT4ge1xuICAgIGxldCB7XG4gICAgICBuZXh0RWwsXG4gICAgICBwcmV2RWxcbiAgICB9ID0gc3dpcGVyLm5hdmlnYXRpb247XG4gICAgbmV4dEVsID0gbWFrZUVsZW1lbnRzQXJyYXkobmV4dEVsKTtcbiAgICBwcmV2RWwgPSBtYWtlRWxlbWVudHNBcnJheShwcmV2RWwpO1xuICAgIGNvbnN0IHRhcmdldEVsID0gZS50YXJnZXQ7XG4gICAgbGV0IHRhcmdldElzQnV0dG9uID0gcHJldkVsLmluY2x1ZGVzKHRhcmdldEVsKSB8fCBuZXh0RWwuaW5jbHVkZXModGFyZ2V0RWwpO1xuICAgIGlmIChzd2lwZXIuaXNFbGVtZW50ICYmICF0YXJnZXRJc0J1dHRvbikge1xuICAgICAgY29uc3QgcGF0aCA9IGUucGF0aCB8fCBlLmNvbXBvc2VkUGF0aCAmJiBlLmNvbXBvc2VkUGF0aCgpO1xuICAgICAgaWYgKHBhdGgpIHtcbiAgICAgICAgdGFyZ2V0SXNCdXR0b24gPSBwYXRoLmZpbmQocGF0aEVsID0+IG5leHRFbC5pbmNsdWRlcyhwYXRoRWwpIHx8IHByZXZFbC5pbmNsdWRlcyhwYXRoRWwpKTtcbiAgICAgIH1cbiAgICB9XG4gICAgaWYgKHN3aXBlci5wYXJhbXMubmF2aWdhdGlvbi5oaWRlT25DbGljayAmJiAhdGFyZ2V0SXNCdXR0b24pIHtcbiAgICAgIGlmIChzd2lwZXIucGFnaW5hdGlvbiAmJiBzd2lwZXIucGFyYW1zLnBhZ2luYXRpb24gJiYgc3dpcGVyLnBhcmFtcy5wYWdpbmF0aW9uLmNsaWNrYWJsZSAmJiAoc3dpcGVyLnBhZ2luYXRpb24uZWwgPT09IHRhcmdldEVsIHx8IHN3aXBlci5wYWdpbmF0aW9uLmVsLmNvbnRhaW5zKHRhcmdldEVsKSkpIHJldHVybjtcbiAgICAgIGxldCBpc0hpZGRlbjtcbiAgICAgIGlmIChuZXh0RWwubGVuZ3RoKSB7XG4gICAgICAgIGlzSGlkZGVuID0gbmV4dEVsWzBdLmNsYXNzTGlzdC5jb250YWlucyhzd2lwZXIucGFyYW1zLm5hdmlnYXRpb24uaGlkZGVuQ2xhc3MpO1xuICAgICAgfSBlbHNlIGlmIChwcmV2RWwubGVuZ3RoKSB7XG4gICAgICAgIGlzSGlkZGVuID0gcHJldkVsWzBdLmNsYXNzTGlzdC5jb250YWlucyhzd2lwZXIucGFyYW1zLm5hdmlnYXRpb24uaGlkZGVuQ2xhc3MpO1xuICAgICAgfVxuICAgICAgaWYgKGlzSGlkZGVuID09PSB0cnVlKSB7XG4gICAgICAgIGVtaXQoJ25hdmlnYXRpb25TaG93Jyk7XG4gICAgICB9IGVsc2Uge1xuICAgICAgICBlbWl0KCduYXZpZ2F0aW9uSGlkZScpO1xuICAgICAgfVxuICAgICAgWy4uLm5leHRFbCwgLi4ucHJldkVsXS5maWx0ZXIoZWwgPT4gISFlbCkuZm9yRWFjaChlbCA9PiBlbC5jbGFzc0xpc3QudG9nZ2xlKHN3aXBlci5wYXJhbXMubmF2aWdhdGlvbi5oaWRkZW5DbGFzcykpO1xuICAgIH1cbiAgfSk7XG4gIGNvbnN0IGVuYWJsZSA9ICgpID0+IHtcbiAgICBzd2lwZXIuZWwuY2xhc3NMaXN0LnJlbW92ZSguLi5zd2lwZXIucGFyYW1zLm5hdmlnYXRpb24ubmF2aWdhdGlvbkRpc2FibGVkQ2xhc3Muc3BsaXQoJyAnKSk7XG4gICAgaW5pdCgpO1xuICAgIHVwZGF0ZSgpO1xuICB9O1xuICBjb25zdCBkaXNhYmxlID0gKCkgPT4ge1xuICAgIHN3aXBlci5lbC5jbGFzc0xpc3QuYWRkKC4uLnN3aXBlci5wYXJhbXMubmF2aWdhdGlvbi5uYXZpZ2F0aW9uRGlzYWJsZWRDbGFzcy5zcGxpdCgnICcpKTtcbiAgICBkZXN0cm95KCk7XG4gIH07XG4gIE9iamVjdC5hc3NpZ24oc3dpcGVyLm5hdmlnYXRpb24sIHtcbiAgICBlbmFibGUsXG4gICAgZGlzYWJsZSxcbiAgICB1cGRhdGUsXG4gICAgaW5pdCxcbiAgICBkZXN0cm95XG4gIH0pO1xufVxuXG5leHBvcnQgeyBOYXZpZ2F0aW9uIGFzIGRlZmF1bHQgfTtcbiIsICJpbXBvcnQgeyBnIGFzIGdldERvY3VtZW50IH0gZnJvbSAnLi4vc2hhcmVkL3Nzci13aW5kb3cuZXNtLm1qcyc7XG5pbXBvcnQgeyBsIGFzIGlzT2JqZWN0LCBlIGFzIGVsZW1lbnRDaGlsZHJlbiB9IGZyb20gJy4uL3NoYXJlZC91dGlscy5tanMnO1xuXG5mdW5jdGlvbiBUaHVtYihfcmVmKSB7XG4gIGxldCB7XG4gICAgc3dpcGVyLFxuICAgIGV4dGVuZFBhcmFtcyxcbiAgICBvblxuICB9ID0gX3JlZjtcbiAgZXh0ZW5kUGFyYW1zKHtcbiAgICB0aHVtYnM6IHtcbiAgICAgIHN3aXBlcjogbnVsbCxcbiAgICAgIG11bHRpcGxlQWN0aXZlVGh1bWJzOiB0cnVlLFxuICAgICAgYXV0b1Njcm9sbE9mZnNldDogMCxcbiAgICAgIHNsaWRlVGh1bWJBY3RpdmVDbGFzczogJ3N3aXBlci1zbGlkZS10aHVtYi1hY3RpdmUnLFxuICAgICAgdGh1bWJzQ29udGFpbmVyQ2xhc3M6ICdzd2lwZXItdGh1bWJzJ1xuICAgIH1cbiAgfSk7XG4gIGxldCBpbml0aWFsaXplZCA9IGZhbHNlO1xuICBsZXQgc3dpcGVyQ3JlYXRlZCA9IGZhbHNlO1xuICBzd2lwZXIudGh1bWJzID0ge1xuICAgIHN3aXBlcjogbnVsbFxuICB9O1xuICBmdW5jdGlvbiBvblRodW1iQ2xpY2soKSB7XG4gICAgY29uc3QgdGh1bWJzU3dpcGVyID0gc3dpcGVyLnRodW1icy5zd2lwZXI7XG4gICAgaWYgKCF0aHVtYnNTd2lwZXIgfHwgdGh1bWJzU3dpcGVyLmRlc3Ryb3llZCkgcmV0dXJuO1xuICAgIGNvbnN0IGNsaWNrZWRJbmRleCA9IHRodW1ic1N3aXBlci5jbGlja2VkSW5kZXg7XG4gICAgY29uc3QgY2xpY2tlZFNsaWRlID0gdGh1bWJzU3dpcGVyLmNsaWNrZWRTbGlkZTtcbiAgICBpZiAoY2xpY2tlZFNsaWRlICYmIGNsaWNrZWRTbGlkZS5jbGFzc0xpc3QuY29udGFpbnMoc3dpcGVyLnBhcmFtcy50aHVtYnMuc2xpZGVUaHVtYkFjdGl2ZUNsYXNzKSkgcmV0dXJuO1xuICAgIGlmICh0eXBlb2YgY2xpY2tlZEluZGV4ID09PSAndW5kZWZpbmVkJyB8fCBjbGlja2VkSW5kZXggPT09IG51bGwpIHJldHVybjtcbiAgICBsZXQgc2xpZGVUb0luZGV4O1xuICAgIGlmICh0aHVtYnNTd2lwZXIucGFyYW1zLmxvb3ApIHtcbiAgICAgIHNsaWRlVG9JbmRleCA9IHBhcnNlSW50KHRodW1ic1N3aXBlci5jbGlja2VkU2xpZGUuZ2V0QXR0cmlidXRlKCdkYXRhLXN3aXBlci1zbGlkZS1pbmRleCcpLCAxMCk7XG4gICAgfSBlbHNlIHtcbiAgICAgIHNsaWRlVG9JbmRleCA9IGNsaWNrZWRJbmRleDtcbiAgICB9XG4gICAgaWYgKHN3aXBlci5wYXJhbXMubG9vcCkge1xuICAgICAgc3dpcGVyLnNsaWRlVG9Mb29wKHNsaWRlVG9JbmRleCk7XG4gICAgfSBlbHNlIHtcbiAgICAgIHN3aXBlci5zbGlkZVRvKHNsaWRlVG9JbmRleCk7XG4gICAgfVxuICB9XG4gIGZ1bmN0aW9uIGluaXQoKSB7XG4gICAgY29uc3Qge1xuICAgICAgdGh1bWJzOiB0aHVtYnNQYXJhbXNcbiAgICB9ID0gc3dpcGVyLnBhcmFtcztcbiAgICBpZiAoaW5pdGlhbGl6ZWQpIHJldHVybiBmYWxzZTtcbiAgICBpbml0aWFsaXplZCA9IHRydWU7XG4gICAgY29uc3QgU3dpcGVyQ2xhc3MgPSBzd2lwZXIuY29uc3RydWN0b3I7XG4gICAgaWYgKHRodW1ic1BhcmFtcy5zd2lwZXIgaW5zdGFuY2VvZiBTd2lwZXJDbGFzcykge1xuICAgICAgc3dpcGVyLnRodW1icy5zd2lwZXIgPSB0aHVtYnNQYXJhbXMuc3dpcGVyO1xuICAgICAgT2JqZWN0LmFzc2lnbihzd2lwZXIudGh1bWJzLnN3aXBlci5vcmlnaW5hbFBhcmFtcywge1xuICAgICAgICB3YXRjaFNsaWRlc1Byb2dyZXNzOiB0cnVlLFxuICAgICAgICBzbGlkZVRvQ2xpY2tlZFNsaWRlOiBmYWxzZVxuICAgICAgfSk7XG4gICAgICBPYmplY3QuYXNzaWduKHN3aXBlci50aHVtYnMuc3dpcGVyLnBhcmFtcywge1xuICAgICAgICB3YXRjaFNsaWRlc1Byb2dyZXNzOiB0cnVlLFxuICAgICAgICBzbGlkZVRvQ2xpY2tlZFNsaWRlOiBmYWxzZVxuICAgICAgfSk7XG4gICAgICBzd2lwZXIudGh1bWJzLnN3aXBlci51cGRhdGUoKTtcbiAgICB9IGVsc2UgaWYgKGlzT2JqZWN0KHRodW1ic1BhcmFtcy5zd2lwZXIpKSB7XG4gICAgICBjb25zdCB0aHVtYnNTd2lwZXJQYXJhbXMgPSBPYmplY3QuYXNzaWduKHt9LCB0aHVtYnNQYXJhbXMuc3dpcGVyKTtcbiAgICAgIE9iamVjdC5hc3NpZ24odGh1bWJzU3dpcGVyUGFyYW1zLCB7XG4gICAgICAgIHdhdGNoU2xpZGVzUHJvZ3Jlc3M6IHRydWUsXG4gICAgICAgIHNsaWRlVG9DbGlja2VkU2xpZGU6IGZhbHNlXG4gICAgICB9KTtcbiAgICAgIHN3aXBlci50aHVtYnMuc3dpcGVyID0gbmV3IFN3aXBlckNsYXNzKHRodW1ic1N3aXBlclBhcmFtcyk7XG4gICAgICBzd2lwZXJDcmVhdGVkID0gdHJ1ZTtcbiAgICB9XG4gICAgc3dpcGVyLnRodW1icy5zd2lwZXIuZWwuY2xhc3NMaXN0LmFkZChzd2lwZXIucGFyYW1zLnRodW1icy50aHVtYnNDb250YWluZXJDbGFzcyk7XG4gICAgc3dpcGVyLnRodW1icy5zd2lwZXIub24oJ3RhcCcsIG9uVGh1bWJDbGljayk7XG4gICAgcmV0dXJuIHRydWU7XG4gIH1cbiAgZnVuY3Rpb24gdXBkYXRlKGluaXRpYWwpIHtcbiAgICBjb25zdCB0aHVtYnNTd2lwZXIgPSBzd2lwZXIudGh1bWJzLnN3aXBlcjtcbiAgICBpZiAoIXRodW1ic1N3aXBlciB8fCB0aHVtYnNTd2lwZXIuZGVzdHJveWVkKSByZXR1cm47XG4gICAgY29uc3Qgc2xpZGVzUGVyVmlldyA9IHRodW1ic1N3aXBlci5wYXJhbXMuc2xpZGVzUGVyVmlldyA9PT0gJ2F1dG8nID8gdGh1bWJzU3dpcGVyLnNsaWRlc1BlclZpZXdEeW5hbWljKCkgOiB0aHVtYnNTd2lwZXIucGFyYW1zLnNsaWRlc1BlclZpZXc7XG5cbiAgICAvLyBBY3RpdmF0ZSB0aHVtYnNcbiAgICBsZXQgdGh1bWJzVG9BY3RpdmF0ZSA9IDE7XG4gICAgY29uc3QgdGh1bWJBY3RpdmVDbGFzcyA9IHN3aXBlci5wYXJhbXMudGh1bWJzLnNsaWRlVGh1bWJBY3RpdmVDbGFzcztcbiAgICBpZiAoc3dpcGVyLnBhcmFtcy5zbGlkZXNQZXJWaWV3ID4gMSAmJiAhc3dpcGVyLnBhcmFtcy5jZW50ZXJlZFNsaWRlcykge1xuICAgICAgdGh1bWJzVG9BY3RpdmF0ZSA9IHN3aXBlci5wYXJhbXMuc2xpZGVzUGVyVmlldztcbiAgICB9XG4gICAgaWYgKCFzd2lwZXIucGFyYW1zLnRodW1icy5tdWx0aXBsZUFjdGl2ZVRodW1icykge1xuICAgICAgdGh1bWJzVG9BY3RpdmF0ZSA9IDE7XG4gICAgfVxuICAgIHRodW1ic1RvQWN0aXZhdGUgPSBNYXRoLmZsb29yKHRodW1ic1RvQWN0aXZhdGUpO1xuICAgIHRodW1ic1N3aXBlci5zbGlkZXMuZm9yRWFjaChzbGlkZUVsID0+IHNsaWRlRWwuY2xhc3NMaXN0LnJlbW92ZSh0aHVtYkFjdGl2ZUNsYXNzKSk7XG4gICAgaWYgKHRodW1ic1N3aXBlci5wYXJhbXMubG9vcCB8fCB0aHVtYnNTd2lwZXIucGFyYW1zLnZpcnR1YWwgJiYgdGh1bWJzU3dpcGVyLnBhcmFtcy52aXJ0dWFsLmVuYWJsZWQpIHtcbiAgICAgIGZvciAobGV0IGkgPSAwOyBpIDwgdGh1bWJzVG9BY3RpdmF0ZTsgaSArPSAxKSB7XG4gICAgICAgIGVsZW1lbnRDaGlsZHJlbih0aHVtYnNTd2lwZXIuc2xpZGVzRWwsIGBbZGF0YS1zd2lwZXItc2xpZGUtaW5kZXg9XCIke3N3aXBlci5yZWFsSW5kZXggKyBpfVwiXWApLmZvckVhY2goc2xpZGVFbCA9PiB7XG4gICAgICAgICAgc2xpZGVFbC5jbGFzc0xpc3QuYWRkKHRodW1iQWN0aXZlQ2xhc3MpO1xuICAgICAgICB9KTtcbiAgICAgIH1cbiAgICB9IGVsc2Uge1xuICAgICAgZm9yIChsZXQgaSA9IDA7IGkgPCB0aHVtYnNUb0FjdGl2YXRlOyBpICs9IDEpIHtcbiAgICAgICAgaWYgKHRodW1ic1N3aXBlci5zbGlkZXNbc3dpcGVyLnJlYWxJbmRleCArIGldKSB7XG4gICAgICAgICAgdGh1bWJzU3dpcGVyLnNsaWRlc1tzd2lwZXIucmVhbEluZGV4ICsgaV0uY2xhc3NMaXN0LmFkZCh0aHVtYkFjdGl2ZUNsYXNzKTtcbiAgICAgICAgfVxuICAgICAgfVxuICAgIH1cbiAgICBjb25zdCBhdXRvU2Nyb2xsT2Zmc2V0ID0gc3dpcGVyLnBhcmFtcy50aHVtYnMuYXV0b1Njcm9sbE9mZnNldDtcbiAgICBjb25zdCB1c2VPZmZzZXQgPSBhdXRvU2Nyb2xsT2Zmc2V0ICYmICF0aHVtYnNTd2lwZXIucGFyYW1zLmxvb3A7XG4gICAgaWYgKHN3aXBlci5yZWFsSW5kZXggIT09IHRodW1ic1N3aXBlci5yZWFsSW5kZXggfHwgdXNlT2Zmc2V0KSB7XG4gICAgICBjb25zdCBjdXJyZW50VGh1bWJzSW5kZXggPSB0aHVtYnNTd2lwZXIuYWN0aXZlSW5kZXg7XG4gICAgICBsZXQgbmV3VGh1bWJzSW5kZXg7XG4gICAgICBsZXQgZGlyZWN0aW9uO1xuICAgICAgaWYgKHRodW1ic1N3aXBlci5wYXJhbXMubG9vcCkge1xuICAgICAgICBjb25zdCBuZXdUaHVtYnNTbGlkZSA9IHRodW1ic1N3aXBlci5zbGlkZXMuZmlsdGVyKHNsaWRlRWwgPT4gc2xpZGVFbC5nZXRBdHRyaWJ1dGUoJ2RhdGEtc3dpcGVyLXNsaWRlLWluZGV4JykgPT09IGAke3N3aXBlci5yZWFsSW5kZXh9YClbMF07XG4gICAgICAgIG5ld1RodW1ic0luZGV4ID0gdGh1bWJzU3dpcGVyLnNsaWRlcy5pbmRleE9mKG5ld1RodW1ic1NsaWRlKTtcbiAgICAgICAgZGlyZWN0aW9uID0gc3dpcGVyLmFjdGl2ZUluZGV4ID4gc3dpcGVyLnByZXZpb3VzSW5kZXggPyAnbmV4dCcgOiAncHJldic7XG4gICAgICB9IGVsc2Uge1xuICAgICAgICBuZXdUaHVtYnNJbmRleCA9IHN3aXBlci5yZWFsSW5kZXg7XG4gICAgICAgIGRpcmVjdGlvbiA9IG5ld1RodW1ic0luZGV4ID4gc3dpcGVyLnByZXZpb3VzSW5kZXggPyAnbmV4dCcgOiAncHJldic7XG4gICAgICB9XG4gICAgICBpZiAodXNlT2Zmc2V0KSB7XG4gICAgICAgIG5ld1RodW1ic0luZGV4ICs9IGRpcmVjdGlvbiA9PT0gJ25leHQnID8gYXV0b1Njcm9sbE9mZnNldCA6IC0xICogYXV0b1Njcm9sbE9mZnNldDtcbiAgICAgIH1cbiAgICAgIGlmICh0aHVtYnNTd2lwZXIudmlzaWJsZVNsaWRlc0luZGV4ZXMgJiYgdGh1bWJzU3dpcGVyLnZpc2libGVTbGlkZXNJbmRleGVzLmluZGV4T2YobmV3VGh1bWJzSW5kZXgpIDwgMCkge1xuICAgICAgICBpZiAodGh1bWJzU3dpcGVyLnBhcmFtcy5jZW50ZXJlZFNsaWRlcykge1xuICAgICAgICAgIGlmIChuZXdUaHVtYnNJbmRleCA+IGN1cnJlbnRUaHVtYnNJbmRleCkge1xuICAgICAgICAgICAgbmV3VGh1bWJzSW5kZXggPSBuZXdUaHVtYnNJbmRleCAtIE1hdGguZmxvb3Ioc2xpZGVzUGVyVmlldyAvIDIpICsgMTtcbiAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgbmV3VGh1bWJzSW5kZXggPSBuZXdUaHVtYnNJbmRleCArIE1hdGguZmxvb3Ioc2xpZGVzUGVyVmlldyAvIDIpIC0gMTtcbiAgICAgICAgICB9XG4gICAgICAgIH0gZWxzZSBpZiAobmV3VGh1bWJzSW5kZXggPiBjdXJyZW50VGh1bWJzSW5kZXggJiYgdGh1bWJzU3dpcGVyLnBhcmFtcy5zbGlkZXNQZXJHcm91cCA9PT0gMSkgO1xuICAgICAgICB0aHVtYnNTd2lwZXIuc2xpZGVUbyhuZXdUaHVtYnNJbmRleCwgaW5pdGlhbCA/IDAgOiB1bmRlZmluZWQpO1xuICAgICAgfVxuICAgIH1cbiAgfVxuICBvbignYmVmb3JlSW5pdCcsICgpID0+IHtcbiAgICBjb25zdCB7XG4gICAgICB0aHVtYnNcbiAgICB9ID0gc3dpcGVyLnBhcmFtcztcbiAgICBpZiAoIXRodW1icyB8fCAhdGh1bWJzLnN3aXBlcikgcmV0dXJuO1xuICAgIGlmICh0eXBlb2YgdGh1bWJzLnN3aXBlciA9PT0gJ3N0cmluZycgfHwgdGh1bWJzLnN3aXBlciBpbnN0YW5jZW9mIEhUTUxFbGVtZW50KSB7XG4gICAgICBjb25zdCBkb2N1bWVudCA9IGdldERvY3VtZW50KCk7XG4gICAgICBjb25zdCBnZXRUaHVtYnNFbGVtZW50QW5kSW5pdCA9ICgpID0+IHtcbiAgICAgICAgY29uc3QgdGh1bWJzRWxlbWVudCA9IHR5cGVvZiB0aHVtYnMuc3dpcGVyID09PSAnc3RyaW5nJyA/IGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3IodGh1bWJzLnN3aXBlcikgOiB0aHVtYnMuc3dpcGVyO1xuICAgICAgICBpZiAodGh1bWJzRWxlbWVudCAmJiB0aHVtYnNFbGVtZW50LnN3aXBlcikge1xuICAgICAgICAgIHRodW1icy5zd2lwZXIgPSB0aHVtYnNFbGVtZW50LnN3aXBlcjtcbiAgICAgICAgICBpbml0KCk7XG4gICAgICAgICAgdXBkYXRlKHRydWUpO1xuICAgICAgICB9IGVsc2UgaWYgKHRodW1ic0VsZW1lbnQpIHtcbiAgICAgICAgICBjb25zdCBldmVudE5hbWUgPSBgJHtzd2lwZXIucGFyYW1zLmV2ZW50c1ByZWZpeH1pbml0YDtcbiAgICAgICAgICBjb25zdCBvblRodW1ic1N3aXBlciA9IGUgPT4ge1xuICAgICAgICAgICAgdGh1bWJzLnN3aXBlciA9IGUuZGV0YWlsWzBdO1xuICAgICAgICAgICAgdGh1bWJzRWxlbWVudC5yZW1vdmVFdmVudExpc3RlbmVyKGV2ZW50TmFtZSwgb25UaHVtYnNTd2lwZXIpO1xuICAgICAgICAgICAgaW5pdCgpO1xuICAgICAgICAgICAgdXBkYXRlKHRydWUpO1xuICAgICAgICAgICAgdGh1bWJzLnN3aXBlci51cGRhdGUoKTtcbiAgICAgICAgICAgIHN3aXBlci51cGRhdGUoKTtcbiAgICAgICAgICB9O1xuICAgICAgICAgIHRodW1ic0VsZW1lbnQuYWRkRXZlbnRMaXN0ZW5lcihldmVudE5hbWUsIG9uVGh1bWJzU3dpcGVyKTtcbiAgICAgICAgfVxuICAgICAgICByZXR1cm4gdGh1bWJzRWxlbWVudDtcbiAgICAgIH07XG4gICAgICBjb25zdCB3YXRjaEZvclRodW1ic1RvQXBwZWFyID0gKCkgPT4ge1xuICAgICAgICBpZiAoc3dpcGVyLmRlc3Ryb3llZCkgcmV0dXJuO1xuICAgICAgICBjb25zdCB0aHVtYnNFbGVtZW50ID0gZ2V0VGh1bWJzRWxlbWVudEFuZEluaXQoKTtcbiAgICAgICAgaWYgKCF0aHVtYnNFbGVtZW50KSB7XG4gICAgICAgICAgcmVxdWVzdEFuaW1hdGlvbkZyYW1lKHdhdGNoRm9yVGh1bWJzVG9BcHBlYXIpO1xuICAgICAgICB9XG4gICAgICB9O1xuICAgICAgcmVxdWVzdEFuaW1hdGlvbkZyYW1lKHdhdGNoRm9yVGh1bWJzVG9BcHBlYXIpO1xuICAgIH0gZWxzZSB7XG4gICAgICBpbml0KCk7XG4gICAgICB1cGRhdGUodHJ1ZSk7XG4gICAgfVxuICB9KTtcbiAgb24oJ3NsaWRlQ2hhbmdlIHVwZGF0ZSByZXNpemUgb2JzZXJ2ZXJVcGRhdGUnLCAoKSA9PiB7XG4gICAgdXBkYXRlKCk7XG4gIH0pO1xuICBvbignc2V0VHJhbnNpdGlvbicsIChfcywgZHVyYXRpb24pID0+IHtcbiAgICBjb25zdCB0aHVtYnNTd2lwZXIgPSBzd2lwZXIudGh1bWJzLnN3aXBlcjtcbiAgICBpZiAoIXRodW1ic1N3aXBlciB8fCB0aHVtYnNTd2lwZXIuZGVzdHJveWVkKSByZXR1cm47XG4gICAgdGh1bWJzU3dpcGVyLnNldFRyYW5zaXRpb24oZHVyYXRpb24pO1xuICB9KTtcbiAgb24oJ2JlZm9yZURlc3Ryb3knLCAoKSA9PiB7XG4gICAgY29uc3QgdGh1bWJzU3dpcGVyID0gc3dpcGVyLnRodW1icy5zd2lwZXI7XG4gICAgaWYgKCF0aHVtYnNTd2lwZXIgfHwgdGh1bWJzU3dpcGVyLmRlc3Ryb3llZCkgcmV0dXJuO1xuICAgIGlmIChzd2lwZXJDcmVhdGVkKSB7XG4gICAgICB0aHVtYnNTd2lwZXIuZGVzdHJveSgpO1xuICAgIH1cbiAgfSk7XG4gIE9iamVjdC5hc3NpZ24oc3dpcGVyLnRodW1icywge1xuICAgIGluaXQsXG4gICAgdXBkYXRlXG4gIH0pO1xufVxuXG5leHBvcnQgeyBUaHVtYiBhcyBkZWZhdWx0IH07XG4iLCAiaW1wb3J0IHsgZCBhcyBub3csIGsgYXMgZWxlbWVudFRyYW5zaXRpb25FbmQgfSBmcm9tICcuLi9zaGFyZWQvdXRpbHMubWpzJztcblxuZnVuY3Rpb24gZnJlZU1vZGUoX3JlZikge1xuICBsZXQge1xuICAgIHN3aXBlcixcbiAgICBleHRlbmRQYXJhbXMsXG4gICAgZW1pdCxcbiAgICBvbmNlXG4gIH0gPSBfcmVmO1xuICBleHRlbmRQYXJhbXMoe1xuICAgIGZyZWVNb2RlOiB7XG4gICAgICBlbmFibGVkOiBmYWxzZSxcbiAgICAgIG1vbWVudHVtOiB0cnVlLFxuICAgICAgbW9tZW50dW1SYXRpbzogMSxcbiAgICAgIG1vbWVudHVtQm91bmNlOiB0cnVlLFxuICAgICAgbW9tZW50dW1Cb3VuY2VSYXRpbzogMSxcbiAgICAgIG1vbWVudHVtVmVsb2NpdHlSYXRpbzogMSxcbiAgICAgIHN0aWNreTogZmFsc2UsXG4gICAgICBtaW5pbXVtVmVsb2NpdHk6IDAuMDJcbiAgICB9XG4gIH0pO1xuICBmdW5jdGlvbiBvblRvdWNoU3RhcnQoKSB7XG4gICAgaWYgKHN3aXBlci5wYXJhbXMuY3NzTW9kZSkgcmV0dXJuO1xuICAgIGNvbnN0IHRyYW5zbGF0ZSA9IHN3aXBlci5nZXRUcmFuc2xhdGUoKTtcbiAgICBzd2lwZXIuc2V0VHJhbnNsYXRlKHRyYW5zbGF0ZSk7XG4gICAgc3dpcGVyLnNldFRyYW5zaXRpb24oMCk7XG4gICAgc3dpcGVyLnRvdWNoRXZlbnRzRGF0YS52ZWxvY2l0aWVzLmxlbmd0aCA9IDA7XG4gICAgc3dpcGVyLmZyZWVNb2RlLm9uVG91Y2hFbmQoe1xuICAgICAgY3VycmVudFBvczogc3dpcGVyLnJ0bCA/IHN3aXBlci50cmFuc2xhdGUgOiAtc3dpcGVyLnRyYW5zbGF0ZVxuICAgIH0pO1xuICB9XG4gIGZ1bmN0aW9uIG9uVG91Y2hNb3ZlKCkge1xuICAgIGlmIChzd2lwZXIucGFyYW1zLmNzc01vZGUpIHJldHVybjtcbiAgICBjb25zdCB7XG4gICAgICB0b3VjaEV2ZW50c0RhdGE6IGRhdGEsXG4gICAgICB0b3VjaGVzXG4gICAgfSA9IHN3aXBlcjtcbiAgICAvLyBWZWxvY2l0eVxuICAgIGlmIChkYXRhLnZlbG9jaXRpZXMubGVuZ3RoID09PSAwKSB7XG4gICAgICBkYXRhLnZlbG9jaXRpZXMucHVzaCh7XG4gICAgICAgIHBvc2l0aW9uOiB0b3VjaGVzW3N3aXBlci5pc0hvcml6b250YWwoKSA/ICdzdGFydFgnIDogJ3N0YXJ0WSddLFxuICAgICAgICB0aW1lOiBkYXRhLnRvdWNoU3RhcnRUaW1lXG4gICAgICB9KTtcbiAgICB9XG4gICAgZGF0YS52ZWxvY2l0aWVzLnB1c2goe1xuICAgICAgcG9zaXRpb246IHRvdWNoZXNbc3dpcGVyLmlzSG9yaXpvbnRhbCgpID8gJ2N1cnJlbnRYJyA6ICdjdXJyZW50WSddLFxuICAgICAgdGltZTogbm93KClcbiAgICB9KTtcbiAgfVxuICBmdW5jdGlvbiBvblRvdWNoRW5kKF9yZWYyKSB7XG4gICAgbGV0IHtcbiAgICAgIGN1cnJlbnRQb3NcbiAgICB9ID0gX3JlZjI7XG4gICAgaWYgKHN3aXBlci5wYXJhbXMuY3NzTW9kZSkgcmV0dXJuO1xuICAgIGNvbnN0IHtcbiAgICAgIHBhcmFtcyxcbiAgICAgIHdyYXBwZXJFbCxcbiAgICAgIHJ0bFRyYW5zbGF0ZTogcnRsLFxuICAgICAgc25hcEdyaWQsXG4gICAgICB0b3VjaEV2ZW50c0RhdGE6IGRhdGFcbiAgICB9ID0gc3dpcGVyO1xuICAgIC8vIFRpbWUgZGlmZlxuICAgIGNvbnN0IHRvdWNoRW5kVGltZSA9IG5vdygpO1xuICAgIGNvbnN0IHRpbWVEaWZmID0gdG91Y2hFbmRUaW1lIC0gZGF0YS50b3VjaFN0YXJ0VGltZTtcbiAgICBpZiAoY3VycmVudFBvcyA8IC1zd2lwZXIubWluVHJhbnNsYXRlKCkpIHtcbiAgICAgIHN3aXBlci5zbGlkZVRvKHN3aXBlci5hY3RpdmVJbmRleCk7XG4gICAgICByZXR1cm47XG4gICAgfVxuICAgIGlmIChjdXJyZW50UG9zID4gLXN3aXBlci5tYXhUcmFuc2xhdGUoKSkge1xuICAgICAgaWYgKHN3aXBlci5zbGlkZXMubGVuZ3RoIDwgc25hcEdyaWQubGVuZ3RoKSB7XG4gICAgICAgIHN3aXBlci5zbGlkZVRvKHNuYXBHcmlkLmxlbmd0aCAtIDEpO1xuICAgICAgfSBlbHNlIHtcbiAgICAgICAgc3dpcGVyLnNsaWRlVG8oc3dpcGVyLnNsaWRlcy5sZW5ndGggLSAxKTtcbiAgICAgIH1cbiAgICAgIHJldHVybjtcbiAgICB9XG4gICAgaWYgKHBhcmFtcy5mcmVlTW9kZS5tb21lbnR1bSkge1xuICAgICAgaWYgKGRhdGEudmVsb2NpdGllcy5sZW5ndGggPiAxKSB7XG4gICAgICAgIGNvbnN0IGxhc3RNb3ZlRXZlbnQgPSBkYXRhLnZlbG9jaXRpZXMucG9wKCk7XG4gICAgICAgIGNvbnN0IHZlbG9jaXR5RXZlbnQgPSBkYXRhLnZlbG9jaXRpZXMucG9wKCk7XG4gICAgICAgIGNvbnN0IGRpc3RhbmNlID0gbGFzdE1vdmVFdmVudC5wb3NpdGlvbiAtIHZlbG9jaXR5RXZlbnQucG9zaXRpb247XG4gICAgICAgIGNvbnN0IHRpbWUgPSBsYXN0TW92ZUV2ZW50LnRpbWUgLSB2ZWxvY2l0eUV2ZW50LnRpbWU7XG4gICAgICAgIHN3aXBlci52ZWxvY2l0eSA9IGRpc3RhbmNlIC8gdGltZTtcbiAgICAgICAgc3dpcGVyLnZlbG9jaXR5IC89IDI7XG4gICAgICAgIGlmIChNYXRoLmFicyhzd2lwZXIudmVsb2NpdHkpIDwgcGFyYW1zLmZyZWVNb2RlLm1pbmltdW1WZWxvY2l0eSkge1xuICAgICAgICAgIHN3aXBlci52ZWxvY2l0eSA9IDA7XG4gICAgICAgIH1cbiAgICAgICAgLy8gdGhpcyBpbXBsaWVzIHRoYXQgdGhlIHVzZXIgc3RvcHBlZCBtb3ZpbmcgYSBmaW5nZXIgdGhlbiByZWxlYXNlZC5cbiAgICAgICAgLy8gVGhlcmUgd291bGQgYmUgbm8gZXZlbnRzIHdpdGggZGlzdGFuY2UgemVybywgc28gdGhlIGxhc3QgZXZlbnQgaXMgc3RhbGUuXG4gICAgICAgIGlmICh0aW1lID4gMTUwIHx8IG5vdygpIC0gbGFzdE1vdmVFdmVudC50aW1lID4gMzAwKSB7XG4gICAgICAgICAgc3dpcGVyLnZlbG9jaXR5ID0gMDtcbiAgICAgICAgfVxuICAgICAgfSBlbHNlIHtcbiAgICAgICAgc3dpcGVyLnZlbG9jaXR5ID0gMDtcbiAgICAgIH1cbiAgICAgIHN3aXBlci52ZWxvY2l0eSAqPSBwYXJhbXMuZnJlZU1vZGUubW9tZW50dW1WZWxvY2l0eVJhdGlvO1xuICAgICAgZGF0YS52ZWxvY2l0aWVzLmxlbmd0aCA9IDA7XG4gICAgICBsZXQgbW9tZW50dW1EdXJhdGlvbiA9IDEwMDAgKiBwYXJhbXMuZnJlZU1vZGUubW9tZW50dW1SYXRpbztcbiAgICAgIGNvbnN0IG1vbWVudHVtRGlzdGFuY2UgPSBzd2lwZXIudmVsb2NpdHkgKiBtb21lbnR1bUR1cmF0aW9uO1xuICAgICAgbGV0IG5ld1Bvc2l0aW9uID0gc3dpcGVyLnRyYW5zbGF0ZSArIG1vbWVudHVtRGlzdGFuY2U7XG4gICAgICBpZiAocnRsKSBuZXdQb3NpdGlvbiA9IC1uZXdQb3NpdGlvbjtcbiAgICAgIGxldCBkb0JvdW5jZSA9IGZhbHNlO1xuICAgICAgbGV0IGFmdGVyQm91bmNlUG9zaXRpb247XG4gICAgICBjb25zdCBib3VuY2VBbW91bnQgPSBNYXRoLmFicyhzd2lwZXIudmVsb2NpdHkpICogMjAgKiBwYXJhbXMuZnJlZU1vZGUubW9tZW50dW1Cb3VuY2VSYXRpbztcbiAgICAgIGxldCBuZWVkc0xvb3BGaXg7XG4gICAgICBpZiAobmV3UG9zaXRpb24gPCBzd2lwZXIubWF4VHJhbnNsYXRlKCkpIHtcbiAgICAgICAgaWYgKHBhcmFtcy5mcmVlTW9kZS5tb21lbnR1bUJvdW5jZSkge1xuICAgICAgICAgIGlmIChuZXdQb3NpdGlvbiArIHN3aXBlci5tYXhUcmFuc2xhdGUoKSA8IC1ib3VuY2VBbW91bnQpIHtcbiAgICAgICAgICAgIG5ld1Bvc2l0aW9uID0gc3dpcGVyLm1heFRyYW5zbGF0ZSgpIC0gYm91bmNlQW1vdW50O1xuICAgICAgICAgIH1cbiAgICAgICAgICBhZnRlckJvdW5jZVBvc2l0aW9uID0gc3dpcGVyLm1heFRyYW5zbGF0ZSgpO1xuICAgICAgICAgIGRvQm91bmNlID0gdHJ1ZTtcbiAgICAgICAgICBkYXRhLmFsbG93TW9tZW50dW1Cb3VuY2UgPSB0cnVlO1xuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgIG5ld1Bvc2l0aW9uID0gc3dpcGVyLm1heFRyYW5zbGF0ZSgpO1xuICAgICAgICB9XG4gICAgICAgIGlmIChwYXJhbXMubG9vcCAmJiBwYXJhbXMuY2VudGVyZWRTbGlkZXMpIG5lZWRzTG9vcEZpeCA9IHRydWU7XG4gICAgICB9IGVsc2UgaWYgKG5ld1Bvc2l0aW9uID4gc3dpcGVyLm1pblRyYW5zbGF0ZSgpKSB7XG4gICAgICAgIGlmIChwYXJhbXMuZnJlZU1vZGUubW9tZW50dW1Cb3VuY2UpIHtcbiAgICAgICAgICBpZiAobmV3UG9zaXRpb24gLSBzd2lwZXIubWluVHJhbnNsYXRlKCkgPiBib3VuY2VBbW91bnQpIHtcbiAgICAgICAgICAgIG5ld1Bvc2l0aW9uID0gc3dpcGVyLm1pblRyYW5zbGF0ZSgpICsgYm91bmNlQW1vdW50O1xuICAgICAgICAgIH1cbiAgICAgICAgICBhZnRlckJvdW5jZVBvc2l0aW9uID0gc3dpcGVyLm1pblRyYW5zbGF0ZSgpO1xuICAgICAgICAgIGRvQm91bmNlID0gdHJ1ZTtcbiAgICAgICAgICBkYXRhLmFsbG93TW9tZW50dW1Cb3VuY2UgPSB0cnVlO1xuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgIG5ld1Bvc2l0aW9uID0gc3dpcGVyLm1pblRyYW5zbGF0ZSgpO1xuICAgICAgICB9XG4gICAgICAgIGlmIChwYXJhbXMubG9vcCAmJiBwYXJhbXMuY2VudGVyZWRTbGlkZXMpIG5lZWRzTG9vcEZpeCA9IHRydWU7XG4gICAgICB9IGVsc2UgaWYgKHBhcmFtcy5mcmVlTW9kZS5zdGlja3kpIHtcbiAgICAgICAgbGV0IG5leHRTbGlkZTtcbiAgICAgICAgZm9yIChsZXQgaiA9IDA7IGogPCBzbmFwR3JpZC5sZW5ndGg7IGogKz0gMSkge1xuICAgICAgICAgIGlmIChzbmFwR3JpZFtqXSA+IC1uZXdQb3NpdGlvbikge1xuICAgICAgICAgICAgbmV4dFNsaWRlID0gajtcbiAgICAgICAgICAgIGJyZWFrO1xuICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgICAgICBpZiAoTWF0aC5hYnMoc25hcEdyaWRbbmV4dFNsaWRlXSAtIG5ld1Bvc2l0aW9uKSA8IE1hdGguYWJzKHNuYXBHcmlkW25leHRTbGlkZSAtIDFdIC0gbmV3UG9zaXRpb24pIHx8IHN3aXBlci5zd2lwZURpcmVjdGlvbiA9PT0gJ25leHQnKSB7XG4gICAgICAgICAgbmV3UG9zaXRpb24gPSBzbmFwR3JpZFtuZXh0U2xpZGVdO1xuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgIG5ld1Bvc2l0aW9uID0gc25hcEdyaWRbbmV4dFNsaWRlIC0gMV07XG4gICAgICAgIH1cbiAgICAgICAgbmV3UG9zaXRpb24gPSAtbmV3UG9zaXRpb247XG4gICAgICB9XG4gICAgICBpZiAobmVlZHNMb29wRml4KSB7XG4gICAgICAgIG9uY2UoJ3RyYW5zaXRpb25FbmQnLCAoKSA9PiB7XG4gICAgICAgICAgc3dpcGVyLmxvb3BGaXgoKTtcbiAgICAgICAgfSk7XG4gICAgICB9XG4gICAgICAvLyBGaXggZHVyYXRpb25cbiAgICAgIGlmIChzd2lwZXIudmVsb2NpdHkgIT09IDApIHtcbiAgICAgICAgaWYgKHJ0bCkge1xuICAgICAgICAgIG1vbWVudHVtRHVyYXRpb24gPSBNYXRoLmFicygoLW5ld1Bvc2l0aW9uIC0gc3dpcGVyLnRyYW5zbGF0ZSkgLyBzd2lwZXIudmVsb2NpdHkpO1xuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgIG1vbWVudHVtRHVyYXRpb24gPSBNYXRoLmFicygobmV3UG9zaXRpb24gLSBzd2lwZXIudHJhbnNsYXRlKSAvIHN3aXBlci52ZWxvY2l0eSk7XG4gICAgICAgIH1cbiAgICAgICAgaWYgKHBhcmFtcy5mcmVlTW9kZS5zdGlja3kpIHtcbiAgICAgICAgICAvLyBJZiBmcmVlTW9kZS5zdGlja3kgaXMgYWN0aXZlIGFuZCB0aGUgdXNlciBlbmRzIGEgc3dpcGUgd2l0aCBhIHNsb3ctdmVsb2NpdHlcbiAgICAgICAgICAvLyBldmVudCwgdGhlbiBkdXJhdGlvbnMgY2FuIGJlIDIwKyBzZWNvbmRzIHRvIHNsaWRlIG9uZSAob3IgemVybyEpIHNsaWRlcy5cbiAgICAgICAgICAvLyBJdCdzIGVhc3kgdG8gc2VlIHRoaXMgd2hlbiBzaW11bGF0aW5nIHRvdWNoIHdpdGggbW91c2UgZXZlbnRzLiBUbyBmaXggdGhpcyxcbiAgICAgICAgICAvLyBsaW1pdCBzaW5nbGUtc2xpZGUgc3dpcGVzIHRvIHRoZSBkZWZhdWx0IHNsaWRlIGR1cmF0aW9uLiBUaGlzIGFsc28gaGFzIHRoZVxuICAgICAgICAgIC8vIG5pY2Ugc2lkZSBlZmZlY3Qgb2YgbWF0Y2hpbmcgc2xpZGUgc3BlZWQgaWYgdGhlIHVzZXIgc3RvcHBlZCBtb3ZpbmcgYmVmb3JlXG4gICAgICAgICAgLy8gbGlmdGluZyBmaW5nZXIgb3IgbW91c2UgdnMuIG1vdmluZyBzbG93bHkgYmVmb3JlIGxpZnRpbmcgdGhlIGZpbmdlci9tb3VzZS5cbiAgICAgICAgICAvLyBGb3IgZmFzdGVyIHN3aXBlcywgYWxzbyBhcHBseSBsaW1pdHMgKGFsYmVpdCBoaWdoZXIgb25lcykuXG4gICAgICAgICAgY29uc3QgbW92ZURpc3RhbmNlID0gTWF0aC5hYnMoKHJ0bCA/IC1uZXdQb3NpdGlvbiA6IG5ld1Bvc2l0aW9uKSAtIHN3aXBlci50cmFuc2xhdGUpO1xuICAgICAgICAgIGNvbnN0IGN1cnJlbnRTbGlkZVNpemUgPSBzd2lwZXIuc2xpZGVzU2l6ZXNHcmlkW3N3aXBlci5hY3RpdmVJbmRleF07XG4gICAgICAgICAgaWYgKG1vdmVEaXN0YW5jZSA8IGN1cnJlbnRTbGlkZVNpemUpIHtcbiAgICAgICAgICAgIG1vbWVudHVtRHVyYXRpb24gPSBwYXJhbXMuc3BlZWQ7XG4gICAgICAgICAgfSBlbHNlIGlmIChtb3ZlRGlzdGFuY2UgPCAyICogY3VycmVudFNsaWRlU2l6ZSkge1xuICAgICAgICAgICAgbW9tZW50dW1EdXJhdGlvbiA9IHBhcmFtcy5zcGVlZCAqIDEuNTtcbiAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgbW9tZW50dW1EdXJhdGlvbiA9IHBhcmFtcy5zcGVlZCAqIDIuNTtcbiAgICAgICAgICB9XG4gICAgICAgIH1cbiAgICAgIH0gZWxzZSBpZiAocGFyYW1zLmZyZWVNb2RlLnN0aWNreSkge1xuICAgICAgICBzd2lwZXIuc2xpZGVUb0Nsb3Nlc3QoKTtcbiAgICAgICAgcmV0dXJuO1xuICAgICAgfVxuICAgICAgaWYgKHBhcmFtcy5mcmVlTW9kZS5tb21lbnR1bUJvdW5jZSAmJiBkb0JvdW5jZSkge1xuICAgICAgICBzd2lwZXIudXBkYXRlUHJvZ3Jlc3MoYWZ0ZXJCb3VuY2VQb3NpdGlvbik7XG4gICAgICAgIHN3aXBlci5zZXRUcmFuc2l0aW9uKG1vbWVudHVtRHVyYXRpb24pO1xuICAgICAgICBzd2lwZXIuc2V0VHJhbnNsYXRlKG5ld1Bvc2l0aW9uKTtcbiAgICAgICAgc3dpcGVyLnRyYW5zaXRpb25TdGFydCh0cnVlLCBzd2lwZXIuc3dpcGVEaXJlY3Rpb24pO1xuICAgICAgICBzd2lwZXIuYW5pbWF0aW5nID0gdHJ1ZTtcbiAgICAgICAgZWxlbWVudFRyYW5zaXRpb25FbmQod3JhcHBlckVsLCAoKSA9PiB7XG4gICAgICAgICAgaWYgKCFzd2lwZXIgfHwgc3dpcGVyLmRlc3Ryb3llZCB8fCAhZGF0YS5hbGxvd01vbWVudHVtQm91bmNlKSByZXR1cm47XG4gICAgICAgICAgZW1pdCgnbW9tZW50dW1Cb3VuY2UnKTtcbiAgICAgICAgICBzd2lwZXIuc2V0VHJhbnNpdGlvbihwYXJhbXMuc3BlZWQpO1xuICAgICAgICAgIHNldFRpbWVvdXQoKCkgPT4ge1xuICAgICAgICAgICAgc3dpcGVyLnNldFRyYW5zbGF0ZShhZnRlckJvdW5jZVBvc2l0aW9uKTtcbiAgICAgICAgICAgIGVsZW1lbnRUcmFuc2l0aW9uRW5kKHdyYXBwZXJFbCwgKCkgPT4ge1xuICAgICAgICAgICAgICBpZiAoIXN3aXBlciB8fCBzd2lwZXIuZGVzdHJveWVkKSByZXR1cm47XG4gICAgICAgICAgICAgIHN3aXBlci50cmFuc2l0aW9uRW5kKCk7XG4gICAgICAgICAgICB9KTtcbiAgICAgICAgICB9LCAwKTtcbiAgICAgICAgfSk7XG4gICAgICB9IGVsc2UgaWYgKHN3aXBlci52ZWxvY2l0eSkge1xuICAgICAgICBlbWl0KCdfZnJlZU1vZGVOb01vbWVudHVtUmVsZWFzZScpO1xuICAgICAgICBzd2lwZXIudXBkYXRlUHJvZ3Jlc3MobmV3UG9zaXRpb24pO1xuICAgICAgICBzd2lwZXIuc2V0VHJhbnNpdGlvbihtb21lbnR1bUR1cmF0aW9uKTtcbiAgICAgICAgc3dpcGVyLnNldFRyYW5zbGF0ZShuZXdQb3NpdGlvbik7XG4gICAgICAgIHN3aXBlci50cmFuc2l0aW9uU3RhcnQodHJ1ZSwgc3dpcGVyLnN3aXBlRGlyZWN0aW9uKTtcbiAgICAgICAgaWYgKCFzd2lwZXIuYW5pbWF0aW5nKSB7XG4gICAgICAgICAgc3dpcGVyLmFuaW1hdGluZyA9IHRydWU7XG4gICAgICAgICAgZWxlbWVudFRyYW5zaXRpb25FbmQod3JhcHBlckVsLCAoKSA9PiB7XG4gICAgICAgICAgICBpZiAoIXN3aXBlciB8fCBzd2lwZXIuZGVzdHJveWVkKSByZXR1cm47XG4gICAgICAgICAgICBzd2lwZXIudHJhbnNpdGlvbkVuZCgpO1xuICAgICAgICAgIH0pO1xuICAgICAgICB9XG4gICAgICB9IGVsc2Uge1xuICAgICAgICBzd2lwZXIudXBkYXRlUHJvZ3Jlc3MobmV3UG9zaXRpb24pO1xuICAgICAgfVxuICAgICAgc3dpcGVyLnVwZGF0ZUFjdGl2ZUluZGV4KCk7XG4gICAgICBzd2lwZXIudXBkYXRlU2xpZGVzQ2xhc3NlcygpO1xuICAgIH0gZWxzZSBpZiAocGFyYW1zLmZyZWVNb2RlLnN0aWNreSkge1xuICAgICAgc3dpcGVyLnNsaWRlVG9DbG9zZXN0KCk7XG4gICAgICByZXR1cm47XG4gICAgfSBlbHNlIGlmIChwYXJhbXMuZnJlZU1vZGUpIHtcbiAgICAgIGVtaXQoJ19mcmVlTW9kZU5vTW9tZW50dW1SZWxlYXNlJyk7XG4gICAgfVxuICAgIGlmICghcGFyYW1zLmZyZWVNb2RlLm1vbWVudHVtIHx8IHRpbWVEaWZmID49IHBhcmFtcy5sb25nU3dpcGVzTXMpIHtcbiAgICAgIGVtaXQoJ19mcmVlTW9kZVN0YXRpY1JlbGVhc2UnKTtcbiAgICAgIHN3aXBlci51cGRhdGVQcm9ncmVzcygpO1xuICAgICAgc3dpcGVyLnVwZGF0ZUFjdGl2ZUluZGV4KCk7XG4gICAgICBzd2lwZXIudXBkYXRlU2xpZGVzQ2xhc3NlcygpO1xuICAgIH1cbiAgfVxuICBPYmplY3QuYXNzaWduKHN3aXBlciwge1xuICAgIGZyZWVNb2RlOiB7XG4gICAgICBvblRvdWNoU3RhcnQsXG4gICAgICBvblRvdWNoTW92ZSxcbiAgICAgIG9uVG91Y2hFbmRcbiAgICB9XG4gIH0pO1xufVxuXG5leHBvcnQgeyBmcmVlTW9kZSBhcyBkZWZhdWx0IH07XG4iLCAiaW1wb3J0IFN3aXBlciBmcm9tICdzd2lwZXInO1xuaW1wb3J0IHsgRnJlZU1vZGUsIE5hdmlnYXRpb24sIFRodW1icyB9IGZyb20gJ3N3aXBlci9tb2R1bGVzJztcblxuaW1wb3J0ICdzd2lwZXIvY3NzJztcbmltcG9ydCAnc3dpcGVyL2Nzcy9mcmVlLW1vZGUnO1xuaW1wb3J0ICdzd2lwZXIvY3NzL25hdmlnYXRpb24nO1xuaW1wb3J0ICdzd2lwZXIvY3NzL3RodW1icyc7XG5cbmV4cG9ydCBkZWZhdWx0IGZ1bmN0aW9uIHN1cHBvcnRTd2lwZXIoeyBzd2lwZXJJc1NxdWFyZSwgaGFzVGh1bWIsIHRodW1iUG9zaXRpb24sIHRodW1iU2NhbGUgfSkge1xuICAgIHJldHVybiB7XG4gICAgICAgIHN3aXBlcjogbnVsbCxcbiAgICAgICAgdGh1bWJTd2lwZXI6IG51bGwsXG4gICAgICAgIHN3aXBlcklzU3F1YXJlLFxuICAgICAgICBoYXNUaHVtYixcbiAgICAgICAgdGh1bWJQb3NpdGlvbixcbiAgICAgICAgdGh1bWJTY2FsZSxcbiAgICAgICAgc3dpcGVySGVpZ2h0OiBudWxsLFxuICAgICAgICBpbml0OiBmdW5jdGlvbigpIHtcbiAgICAgICAgICAgIGxldCBzd2lwZXJPcHRpb25zID0ge1xuICAgICAgICAgICAgICAgIG1vZHVsZXM6IFtGcmVlTW9kZSwgTmF2aWdhdGlvbiwgVGh1bWJzXSxcbiAgICAgICAgICAgICAgICBsb29wOiB0cnVlLFxuICAgICAgICAgICAgICAgIHNwYWNlQmV0d2VlbjogMTAsICAgICAgIC8vIFx1NkVEMVx1NTJBOFx1NjVGNlx1NEUyNFx1NEUyQVx1NUU3Qlx1NzA2Rlx1NzI0N1x1NEU0Qlx1OTVGNFx1NzY4NFx1OERERFx1NzlCQiBweFxuICAgICAgICAgICAgICAgIHNsaWRlc1BlclZpZXc6IDEsICAgICAgIC8vIFx1NTNFRlx1ODlDNlx1NTMzQVx1NTdERlx1NTNFRlx1ODlDMVx1NUU3Qlx1NzA2Rlx1NzI0N1x1NjU3MFx1OTFDRlxuICAgICAgICAgICAgICAgIG5hdmlnYXRpb246IHtcbiAgICAgICAgICAgICAgICAgICAgbmV4dEVsOiBcIi5zd2lwZXItYnV0dG9uLW5leHRcIixcbiAgICAgICAgICAgICAgICAgICAgcHJldkVsOiBcIi5zd2lwZXItYnV0dG9uLXByZXZcIixcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9XG5cbiAgICAgICAgICAgIGlmICh0aGlzLmhhc1RodW1iKSB7ICAgICAgICAvLyBcdTUzMDVcdTU0MkJcdTdGMjlcdTc1NjUgc3dpcGVyXG4gICAgICAgICAgICAgICAgdGhpcy50aHVtYlN3aXBlciA9IG5ldyBTd2lwZXIoXCIuZGV0YWlsLXN3aXBlci10aHVtYnNcIiwge1xuICAgICAgICAgICAgICAgICAgICBtb2R1bGVzOiBbRnJlZU1vZGUsIE5hdmlnYXRpb24sIFRodW1ic10sXG4gICAgICAgICAgICAgICAgICAgIGxvb3A6IHRydWUsXG4gICAgICAgICAgICAgICAgICAgIHNwYWNlQmV0d2VlbjogMTAsICAgICAgIC8vIFx1NkVEMVx1NTJBOFx1NjVGNlx1NEUyNFx1NEUyQVx1NUU3Qlx1NzA2Rlx1NzI0N1x1NEU0Qlx1OTVGNFx1NzY4NFx1OERERFx1NzlCQiBweFxuICAgICAgICAgICAgICAgICAgICBzbGlkZXNQZXJWaWV3OiA2LCAgICAgICAvLyBcdTUzRUZcdTg5QzZcdTUzM0FcdTU3REZcdTUzRUZcdTg5QzFcdTVFN0JcdTcwNkZcdTcyNDdcdTY1NzBcdTkxQ0ZcbiAgICAgICAgICAgICAgICAgICAgZnJlZU1vZGU6IHRydWUsXG4gICAgICAgICAgICAgICAgICAgIHdhdGNoU2xpZGVzUHJvZ3Jlc3M6IHRydWUsICAgICAgLy8gXHU1NDJGXHU3NTI4XHU2QjY0XHU1MjlGXHU4MEZEXHU0RUU1XHU4QkExXHU3Qjk3XHU2QkNGXHU0RTJBXHU1RTdCXHU3MDZGXHU3MjQ3XHU3Njg0XHU4RkRCXHU1RUE2XHU1NDhDXHU1M0VGXHU4OUMxXHU2MDI3KFx1ODlDNlx1NTNFM1x1NEUyRFx1NzY4NFx1NUU3Qlx1NzA2Rlx1NzI0N1x1NUMwNlx1NjcwOVx1OTg5RFx1NTkxNlx1NzY4NFx1NTNFRlx1ODlDMVx1N0M3QlxuICAgICAgICAgICAgICAgIH0pO1xuXG4gICAgICAgICAgICAgICAgc3dpcGVyT3B0aW9uc1sndGh1bWJzJ10gPSB7XG4gICAgICAgICAgICAgICAgICAgIHN3aXBlcjogdGhpcy50aHVtYlN3aXBlcixcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9XG5cbiAgICAgICAgICAgIHRoaXMuc3dpcGVyID0gbmV3IFN3aXBlcihcIi5kZXRhaWwtc3dpcGVyXCIsIHN3aXBlck9wdGlvbnMpO1xuICAgICAgICB9LFxuICAgICAgICBzZXRTd2lwZXJIZWlnaHQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIGlmICghdGhpcy5zd2lwZXJJc1NxdWFyZSkge1xuICAgICAgICAgICAgICAgIHRoaXMuc3dpcGVySGVpZ2h0ID0gdGhpcy4kaGVpZ2h0XG4gICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgIGlmICh0aGlzLmhhc1RodW1iKSB7XG4gICAgICAgICAgICAgICAgICAgIGlmIChbJ2xlZnQnLCAncmlnaHQnXS5pbmNsdWRlcyh0aGlzLnRodW1iUG9zaXRpb24pKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICB0aGlzLnN3aXBlckhlaWdodCA9ICgodGhpcy4kd2lkdGggKiAoMTAwIC0gdGhpcy50aHVtYlNjYWxlKSkgLyAxMDApLnRvRml4ZWQoMik7XG4gICAgICAgICAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgICAgICAgICB0aGlzLnN3aXBlckhlaWdodCA9ICh0aGlzLiR3aWR0aCAvICgoMTAwIC0gdGhpcy50aHVtYlNjYWxlKSAvIDEwMCkpLnRvRml4ZWQoMik7XG4gICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgICAgICB0aGlzLnN3aXBlckhlaWdodCA9IHRoaXMuJHdpZHRoO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgIH1cbn1cbiJdLAogICJtYXBwaW5ncyI6ICI7QUFZQSxTQUFTLFNBQVMsS0FBSztBQUNyQixTQUFPLFFBQVEsUUFBUSxPQUFPLFFBQVEsWUFBWSxpQkFBaUIsT0FBTyxJQUFJLGdCQUFnQjtBQUNoRztBQUNBLFNBQVMsT0FBTyxRQUFRLEtBQUs7QUFDM0IsTUFBSSxXQUFXLFFBQVE7QUFDckIsYUFBUyxDQUFDO0FBQUEsRUFDWjtBQUNBLE1BQUksUUFBUSxRQUFRO0FBQ2xCLFVBQU0sQ0FBQztBQUFBLEVBQ1Q7QUFDQSxTQUFPLEtBQUssR0FBRyxFQUFFLFFBQVEsU0FBTztBQUM5QixRQUFJLE9BQU8sT0FBTyxHQUFHLE1BQU07QUFBYSxhQUFPLEdBQUcsSUFBSSxJQUFJLEdBQUc7QUFBQSxhQUFXLFNBQVMsSUFBSSxHQUFHLENBQUMsS0FBSyxTQUFTLE9BQU8sR0FBRyxDQUFDLEtBQUssT0FBTyxLQUFLLElBQUksR0FBRyxDQUFDLEVBQUUsU0FBUyxHQUFHO0FBQ3ZKLGFBQU8sT0FBTyxHQUFHLEdBQUcsSUFBSSxHQUFHLENBQUM7QUFBQSxJQUM5QjtBQUFBLEVBQ0YsQ0FBQztBQUNIO0FBQ0EsSUFBTSxjQUFjO0FBQUEsRUFDbEIsTUFBTSxDQUFDO0FBQUEsRUFDUCxtQkFBbUI7QUFBQSxFQUFDO0FBQUEsRUFDcEIsc0JBQXNCO0FBQUEsRUFBQztBQUFBLEVBQ3ZCLGVBQWU7QUFBQSxJQUNiLE9BQU87QUFBQSxJQUFDO0FBQUEsSUFDUixVQUFVO0FBQUEs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TUFBTSxLQUFLLGNBQWUsS0FBSyxRQUFRLENBQUM7QUFBQSxVQUNqRixPQUFPO0FBQ0gsaUJBQUssZ0JBQWdCLEtBQUssV0FBVyxNQUFNLEtBQUssY0FBYyxNQUFNLFFBQVEsQ0FBQztBQUFBLFVBQ2pGO0FBQUEsUUFDSixPQUFPO0FBQ0gsZUFBSyxlQUFlLEtBQUs7QUFBQSxRQUM3QjtBQUFBLE1BQ0o7QUFBQSxJQUNKO0FBQUEsRUFDSjtBQUNKOyIsCiAgIm5hbWVzIjogWyJjbGFzc2VzIiwgImdldENvbXB1dGVkU3R5bGUiLCAid2luZG93IiwgImlzT2JqZWN0IiwgImV4dGVuZCIsICJjbGFzc2VzIiwgIndpbmRvdyIsICJ3aW5kb3ciLCAiZG9jdW1lbnQiLCAic3VwcG9ydCIsICJvYnNlcnZlclVwZGF0ZSIsICJldmVudHMiLCAiZXZlbnQiLCAic2xpZGUiLCAidHJhbnNsYXRlIiwgInJlYWxJbmRleCIsICJtaW5UcmFuc2xhdGUiLCAibWF4VHJhbnNsYXRlIiwgInRyYW5zaXRpb25FbmQiLCAic2xpZGVUbyIsICJzZXRUcmFuc2xhdGUiLCAiaSIsICJpbmNyZW1lbnQiLCAiYnJlYWtwb2ludHMiLCAiZXh0ZW5kIiwgInVwZGF0ZSIsICJpc09iamVjdCIsICJ1cGRhdGUiLCAiZG9jdW1lbnQiLCAib25Ub3VjaFN0YXJ0IiwgInRyYW5zbGF0ZSIsICJvblRvdWNoTW92ZSIsICJvblRvdWNoRW5kIl0KfQo=
