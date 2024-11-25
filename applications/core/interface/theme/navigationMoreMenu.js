customElements.define('i-navigation-menu', class extends HTMLElement {

	constructor () {
		super();
		this.menu = this.querySelector('.ipsNavBar');
		this.moreLi = this.menu.querySelector("[data-el='nav-more']");
		this.moreMenu = this.moreLi.querySelector(".ipsNav__dropdown");
	}

	emptyMoreMenu() {
		// Move all children back into main
		this.moreLi.before(...this.moreMenu.children);
		// Then hide the More menu
		this.moreLi.hidden = true;
	}

	fillMoreMenu(clientWidth){

		let scrollWidth = this.menu.scrollWidth;

		// If there's no overflow, return early
		if(scrollWidth <= clientWidth) return;
		// Show More menu
		this.moreLi.hidden = false;
		// While the navigation list overflows, move items into the More menu
		while(scrollWidth > clientWidth){
			// If the More menu is the first element, end the while loop
			if(!this.moreLi.previousElementSibling){ break; }
			// Move last link into More menu
			this.moreMenu.prepend(this.moreLi.previousElementSibling);

			scrollWidth = this.menu.scrollWidth;
		}
	}

	updateAttributes(){

		// Reapply the light-dismiss functionality to items which have been moved from the More menu back to the main menu
		this.menu.querySelectorAll('[data-ips-hidden-light-dismiss-disabled]').forEach(el => {
			el.removeAttribute('data-ips-hidden-light-dismiss-disabled');
			el.setAttribute('data-ips-hidden-light-dismiss', '');
		});

		// Then loop through menus inside More menu and remove their light-dismiss feature, and add a -disabled variant so we can reapply the correct attribute later
		this.moreMenu.querySelectorAll('[data-ips-hidden-light-dismiss]').forEach(el => {
			el.removeAttribute('data-ips-hidden-light-dismiss');
			el.setAttribute('data-ips-hidden-light-dismiss-disabled', '');
		});

		// If the active link is inside the More menu... 
		if(this.moreMenu.querySelector("[aria-current]")){
			// ..add [data-active] to "More"
			this.moreLi.setAttribute("data-active", "");			
			// ..and expand the active dropdown
			this.moreMenu.querySelectorAll(':scope > [data-active]').forEach(el => {
				const button = el.querySelector(":scope > [aria-expanded]");
				if(button){
					button.setAttribute("aria-expanded", "true");
				}
				const menu = el.querySelector(":scope > .ipsNav__dropdown");
				if(menu){
					menu.hidden = false;
				}
			});
		} else {
			// Otherwise remove [data-active] from the More menu
			this.moreLi.removeAttribute("data-active");
		}
	}

	connectedCallback(){

		// Empty More menu and refill it on resize
		this.ro = new ResizeObserver(entries => {
			for (let entry of entries) {

				let entryWidth = Math.ceil(entry.contentRect.width);

				if(this.lastWidth && entryWidth > this.lastWidth){
					this.emptyMoreMenu();
				}

				this.fillMoreMenu(entryWidth);
				this.updateAttributes();

				this.lastWidth = entryWidth;
		 	}
		});
		
		this.ro.observe(this.menu);
	}
});