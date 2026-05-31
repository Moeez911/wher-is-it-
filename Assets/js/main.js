document.addEventListener("DOMContentLoaded", () => {
    const filterButtons = document.querySelectorAll(".filter-btn");
    const itemCards = document.querySelectorAll(".item-card");

    filterButtons.forEach(btn => {
        btn.addEventListener("click", () => {
            filterButtons.forEach(b => b.classList.remove("active"));
            btn.classList.add("active");
            const type = btn.getAttribute("data-type");
            itemCards.forEach(card => {
                if (type === "all" || card.getAttribute("data-board") === type) {
                    card.style.display = "flex";
                } else {
                    card.style.display = "none";
                }
            });
        });
    });

    const openDrawerBtn = document.getElementById("openDrawerBtn");
    const postDrawer = document.getElementById("postDrawer");
    const closeDrawerBtn = document.getElementById("closeDrawerBtn");

    if (openDrawerBtn && postDrawer) openDrawerBtn.addEventListener("click", () => postDrawer.classList.add("open"));
    if (closeDrawerBtn && postDrawer) closeDrawerBtn.addEventListener("click", () => postDrawer.classList.remove("open"));
});