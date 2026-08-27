<script>
  document.addEventListener('DOMContentLoaded', function() {
    class ResponsiveNavigation {
      constructor() {
        this.mainNav = document.getElementById('mainNavigation');
        this.overflowContainer = document.getElementById('overflowItems');
        this.dropdownButton = document.getElementById('actiondropdown');
        this.allNavItems = [];
        if (!this.mainNav || !this.overflowContainer || !this.dropdownButton) {
          return;
        }
        this.init();
      }

      init() {
        this.allNavItems = Array.from(this.mainNav.querySelectorAll('.nav-item')).map(item => {
          const priorityClass = Array.from(item.classList).find(cls => cls.startsWith('nav-priority-'));
          const priority = priorityClass ? parseInt(priorityClass.split('-')[2]) : 999;
          return {
            element: item,
            priority: priority,
            isActive: item.querySelector('.nav-link.active') !== null
          };
        }).sort((a, b) => a.priority - b.priority);

        this.handleResize();

        let resizeTimeout;
        window.addEventListener('resize', () => {
          clearTimeout(resizeTimeout);
          resizeTimeout = setTimeout(() => this.handleResize(), 100);
        });

        window.addEventListener('load', () => {
          setTimeout(() => this.handleResize(), 200);
        });
      }

      handleResize() {
        this.resetNavigation();
        requestAnimationFrame(() => {
          requestAnimationFrame(() => this.redistributeItems());
        });
      }

      resetNavigation() {
        this.overflowContainer.innerHTML = '';
        this.mainNav.innerHTML = '';
        this.allNavItems.forEach(item => {
          this.mainNav.appendChild(item.element);
        });
      }

      redistributeItems() {
        const container = this.mainNav.closest('.card-body');
        if (!container) return;

        const containerWidth = container.getBoundingClientRect().width;
        const dropdownWidth = this.dropdownButton.offsetWidth + 10;
        const visibleItems = [];
        const overflowItems = [];
        let currentWidth = 0;

        const itemWidths = this.allNavItems.map(item => {
          return { item, width: this.getItemWidth(item.element) };
        });
        const totalItemsWidth = itemWidths.reduce((sum, row) => sum + row.width, 0);

        const containerStyles = window.getComputedStyle(container);
        const containerPadding = parseFloat(containerStyles.paddingLeft) + parseFloat(containerStyles.paddingRight);
        const usableWidth = containerWidth - containerPadding - 20;

        if (totalItemsWidth <= usableWidth) {
          this.allNavItems.forEach(item => visibleItems.push(item));
        } else {
          const availableWidth = usableWidth - dropdownWidth;
          for (let i = 0; i < itemWidths.length; i++) {
            const { item, width } = itemWidths[i];
            if (currentWidth + width <= availableWidth) {
              currentWidth += width;
              visibleItems.push(item);
            } else {
              overflowItems.push(item);
            }
          }
          if (visibleItems.length === 0 && this.allNavItems.length > 0) {
            visibleItems.push(this.allNavItems[0]);
            overflowItems.unshift(...this.allNavItems.slice(1));
          }
        }

        this.updateNavigation(visibleItems, overflowItems);
      }

      getItemWidth(element) {
        const clone = element.cloneNode(true);
        clone.style.cssText = 'visibility:hidden;position:absolute;white-space:nowrap;top:-9999px;left:-9999px;pointer-events:none;z-index:-1;';
        const container = this.mainNav.parentNode;
        container.appendChild(clone);
        const width = Math.ceil(clone.getBoundingClientRect().width) + 6;
        container.removeChild(clone);
        return width;
      }

      updateNavigation(visibleItems, overflowItems) {
        this.mainNav.innerHTML = '';
        visibleItems.forEach(item => this.mainNav.appendChild(item.element));
        this.overflowContainer.innerHTML = '';

        if (overflowItems.length > 0) {
          this.dropdownButton.style.display = 'flex';
          overflowItems.forEach(item => {
            this.overflowContainer.appendChild(this.createDropdownItem(item));
          });
        } else {
          this.dropdownButton.style.display = 'none';
        }
      }

      createDropdownItem(navItem) {
        const link = navItem.element.querySelector('.nav-link');
        const href = link.getAttribute('href');
        const icon = link.querySelector('i');
        const text = link.textContent.trim();
        const isActive = link.classList.contains('active');

        const dropdownItem = document.createElement('a');
        dropdownItem.className = `dropdown-item overflow-nav-item ${isActive ? 'active' : ''}`;
        dropdownItem.href = href;

        if (icon) {
          const iconClone = icon.cloneNode(true);
          iconClone.className = icon.className.replace('me-1_5', 'me-2');
          dropdownItem.appendChild(iconClone);
        }
        dropdownItem.appendChild(document.createTextNode(' ' + text));
        return dropdownItem;
      }
    }

    const responsiveNav = new ResponsiveNavigation();
    setTimeout(() => {
      if (responsiveNav.handleResize) {
        responsiveNav.handleResize();
      }
    }, 500);
  });
</script>
