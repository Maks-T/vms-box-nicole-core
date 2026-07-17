<div class="col-span-full" style="width: 100%; margin-bottom: 8px;">
  <a href="/calculator" target="_blank"
     style="display: block; padding: 20px; background: linear-gradient(90deg, #F59E0B 0%, #EA580C 100%); border-radius: 12px; text-decoration: none; box-shadow: 0 1px 3px 0 rgba(0,0,0,0.05); transition: opacity 0.2s;"
     onmouseover="this.style.opacity='0.95'"
     onmouseout="this.style.opacity='1'">

    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">

      <div style="display: flex; align-items: center; gap: 16px; color: white;">

        <div
          style="padding: 12px; background: rgba(255, 255, 255, 0.15); border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
          <svg style="width: 32px; height: 32px; color: white; display: block;" fill="none" viewBox="0 0 24 24"
               stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M15.75 15.75V18m-3-3V18m-3-3V18m3-3h.008v.008H12V15.75zm0-3h.008v.008H12V12.75zm0-3h.008v.008H12V9.75zm-3 3h.008v.008H9V12.75zm0-3h.008v.008H9V9.75zm6 3h.008v.008h-.008V12.75zm0-3h.008v.008h-.008V9.75zM6.75 22.5h10.5a2.25 2.25 0 0 0 2.25-2.25V3.75a2.25 2.25 0 0 0-2.25-2.25H6.75A2.25 2.25 0 0 0 4.5 3.75v16.5A2.25 2.25 0 0 0 6.75 22.5z"/>
          </svg>
        </div>

        <div style="display: flex; flex-direction: column; gap: 4px; text-align: left;">
          <h2
            style="font-size: 1.15rem; font-weight: 700; margin: 0; padding: 0; line-height: 1.2; color: white; font-family: system-ui, -apple-system, sans-serif;">
            {{ __('Interactive Calculator') }}
          </h2>
          <p
            style="font-size: 0.85rem; color: rgba(255, 255, 255, 0.9); margin: 0; padding: 0; font-family: system-ui, -apple-system, sans-serif; line-height: 1.3;">
            {{ __('Open the CPQ widget in a new tab') }}
          </p>
        </div>
      </div>

      <div
        style="background: rgba(255, 255, 255, 0.2); color: white; padding: 8px 16px; border-radius: 8px; font-weight: 600; font-size: 0.85rem; display: flex; align-items: center; gap: 8px; transition: background 0.2s;"
        onmouseover="this.style.background='rgba(255, 255, 255, 0.3)'"
        onmouseout="this.style.background='rgba(255, 255, 255, 0.2)'">
        <span style="font-family: system-ui, -apple-system, sans-serif;">{{ __('Go to App') }}</span>
        <svg style="width: 16px; height: 16px; color: white; display: block;" fill="none" viewBox="0 0 24 24"
             stroke-width="2.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
        </svg>
      </div>

    </div>
  </a>
</div>
