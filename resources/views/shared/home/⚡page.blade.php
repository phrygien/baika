<?php

use Livewire\Component;

new class extends Component
{
    function save()
    {
        dd('Saved !');
    }
};
?>

<div>

<div class="relative bg-gray-900">
  <div aria-hidden="true" class="absolute inset-0 overflow-hidden">
    <img src="https://tailwindui.com/plus-assets/img/ecommerce-images/home-page-01-hero-full-width.jpg" alt="" class="size-full object-cover">
  </div>
  <div aria-hidden="true" class="absolute inset-0 bg-gray-900 opacity-50"></div>


  <div class="relative mx-auto flex max-w-3xl flex-col items-center px-6 py-32 text-center sm:py-64 lg:px-0">
    <h1 class="text-4xl font-bold tracking-tight text-white lg:text-6xl">New arrivals are here</h1>
    <p class="mt-4 text-xl text-white">The new arrivals have, well, newly arrived. Check out the latest options from our summer small-batch release while they're still in stock.</p>
    <a href="#" class="mt-8 inline-block rounded-md border border-transparent bg-white px-8 py-3 text-base font-medium text-gray-900 hover:bg-gray-100">Shop New Arrivals</a>
  </div>
</div>
    <livewire:shared::collections.page />

  <section aria-labelledby="social-impact-heading" class="mx-auto max-w-7xl px-4 pt-24 sm:px-6 sm:pt-32 lg:px-8">
    <div class="relative overflow-hidden rounded-lg">
      <div class="absolute inset-0"><img src="https://tailwindui.com/plus-assets/img/ecommerce-images/home-page-01-feature-section-01.jpg" alt="" class="size-full object-cover"></div>
      <div class="relative bg-gray-900/75 px-6 py-32 sm:px-12 sm:py-40 lg:px-16">
        <div class="relative mx-auto flex max-w-3xl flex-col items-center text-center">
          <h2 id="social-impact-heading" class="text-3xl font-bold tracking-tight text-white sm:text-4xl"><span class="block sm:inline">Level up</span> <span class="block sm:inline">your desk</span></h2>
          <p class="mt-3 text-xl text-white">Make your desk beautiful and organized. Post a picture to social media and watch it get more likes than life-changing announcements.</p>
          <a href="#" class="mt-8 block w-full rounded-md border border-transparent bg-white px-8 py-3 text-base font-medium text-gray-900 hover:bg-gray-100 sm:w-auto">Shop Workspace</a>
        </div>
      </div>
    </div>
  </section>

  <section aria-labelledby="collection-heading" class="mx-auto max-w-xl px-4 pt-24 sm:px-6 sm:pt-32 lg:max-w-7xl lg:px-8">
    <h2 id="collection-heading" class="text-2xl font-bold tracking-tight text-gray-900">Shop by Collection</h2>
    <p class="mt-4 text-base text-gray-500">Each season, we collaborate with world-class designers to create a collection inspired by the natural world.</p>
    <div class="mt-10 space-y-12 lg:grid lg:grid-cols-3 lg:gap-x-8 lg:space-y-0">
      <a href="#" class="group block"><img src="https://tailwindui.com/plus-assets/img/ecommerce-images/home-page-01-collection-01.jpg" alt="" class="aspect-3/2 w-full rounded-lg object-cover group-hover:opacity-75 lg:aspect-5/6"><h3 class="mt-4 text-base font-semibold text-gray-900">Handcrafted Collection</h3><p class="mt-2 text-sm text-gray-500">Keep your phone, keys, and wallet together, so you can lose everything at once.</p></a>
      <a href="#" class="group block"><img src="https://tailwindui.com/plus-assets/img/ecommerce-images/home-page-01-collection-02.jpg" alt="" class="aspect-3/2 w-full rounded-lg object-cover group-hover:opacity-75 lg:aspect-5/6"><h3 class="mt-4 text-base font-semibold text-gray-900">Organized Desk Collection</h3><p class="mt-2 text-sm text-gray-500">The rest of the house will still be a mess, but your desk will look great.</p></a>
      <a href="#" class="group block"><img src="https://tailwindui.com/plus-assets/img/ecommerce-images/home-page-01-collection-03.jpg" alt="" class="aspect-3/2 w-full rounded-lg object-cover group-hover:opacity-75 lg:aspect-5/6"><h3 class="mt-4 text-base font-semibold text-gray-900">Focus Collection</h3><p class="mt-2 text-sm text-gray-500">Be more productive than enterprise project managers with a single piece of paper.</p></a>
    </div>
  </section>

  <section aria-labelledby="comfort-heading" class="mx-auto max-w-7xl px-4 py-24 sm:px-6 sm:py-32 lg:px-8">
    <div class="relative overflow-hidden rounded-lg">
      <div class="absolute inset-0"><img src="https://tailwindui.com/plus-assets/img/ecommerce-images/home-page-01-feature-section-02.jpg" alt="" class="size-full object-cover"></div>
      <div class="relative bg-gray-900/75 px-6 py-32 sm:px-12 sm:py-40 lg:px-16">
        <div class="relative mx-auto flex max-w-3xl flex-col items-center text-center">
          <h2 id="comfort-heading" class="text-3xl font-bold tracking-tight text-white sm:text-4xl">Simple productivity</h2>
          <p class="mt-3 text-xl text-white">Endless tasks, limited hours, a single piece of paper. Just the undeniable urge to fill empty circles.</p>
          <a href="#" class="mt-8 block w-full rounded-md border border-transparent bg-white px-8 py-3 text-base font-medium text-gray-900 hover:bg-gray-100 sm:w-auto">Shop Focus</a>
        </div>
      </div>
    </div>
  </section>
</div>
