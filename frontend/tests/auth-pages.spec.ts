import { test, expect } from '@playwright/test'

// Test Sign In Page

// helpers to stub auth endpoints
async function allowAuth(page, ok: boolean) {
  await page.route('**/api/v1/auth/me', route => {
    route.fulfill({ status: ok ? 200 : 401, contentType: 'application/json', body: '{}' })
  })
}

// root behaviour

test.describe('Root path', () => {
  test('redirects to signin when user is not logged', async ({ page }) => {
    await allowAuth(page, false)
    await page.goto('/')
    await expect(page).toHaveURL('/signin')
  })

  test('redirects to dashboard when user is already logged', async ({ page }) => {
    await allowAuth(page, true)
    await page.goto('/')
    await expect(page).toHaveURL('/dashboard')
  })
})

// dashboard protection

test.describe('Dashboard Guard', () => {
  test('unauthenticated visitor is sent to signin', async ({ page }) => {
    await allowAuth(page, false)
    await page.goto('/dashboard')
    await expect(page).toHaveURL('/signin')
  })

  test('authenticated visitor stays on dashboard', async ({ page }) => {
    await allowAuth(page, true)
    await page.goto('/dashboard')
    await expect(page).toHaveURL('/dashboard')
  })
})

test.describe('Sign In Page', () => {
  test('should render all elements and allow navigation to Sign Up', async ({ page }) => {
    await page.goto('/signin')

    await test.step('Verify page title and main elements', async () => {
      await expect(page.getByTestId('login-title')).toHaveText('Entrar')
      await expect(page.getByTestId('login-username')).toBeVisible()
      await expect(page.getByTestId('login-password')).toBeVisible()
      await expect(page.getByTestId('login-remember')).toBeVisible()
      await expect(page.getByTestId('login-submit')).toBeVisible()
      await expect(page.getByTestId('login-forgot')).toBeVisible()
      await expect(page.getByTestId('login-signup')).toBeVisible()
    })

    await test.step('Test navigation links', async () => {
      await page.getByTestId('login-signup').click()
      await expect(page).toHaveURL('/signup')
      await page.goBack()

      await page.getByTestId('login-forgot').click()
      await expect(page).toHaveURL('/forgot')
      await page.goBack()
    })
  })

  test('should show validation errors on empty submit', async ({ page }) => {
    await page.goto('/signin')

    await test.step('Submit empty form', async () => {
      await page.getByTestId('login-submit').click()
    })

    await test.step('Verify form remains on page (HTML5 validation prevents submission)', async () => {
      // HTML5 validation prevents form submission with empty required fields
      await expect(page.getByTestId('login-username')).toBeVisible()
      await expect(page.getByTestId('login-password')).toBeVisible()
      await expect(page).toHaveURL('/signin') // Should not navigate
    })
  })

  test('should show email verification success message when verified=1 parameter is present', async ({ page }) => {
    await page.goto('/signin?verified=1')

    await test.step('Verify success message is displayed', async () => {
      await expect(page.getByText('✅ Email verificado com sucesso! Agora você pode fazer login.')).toBeVisible()
      await expect(page.getByRole('status')).toHaveText('✅ Email verificado com sucesso! Agora você pode fazer login.')
    })

    await test.step('Verify page title and form elements still present', async () => {
      await expect(page.getByTestId('login-title')).toHaveText('Entrar')
      await expect(page.getByTestId('login-username')).toBeVisible()
      await expect(page.getByTestId('login-password')).toBeVisible()
      await expect(page.getByTestId('login-submit')).toBeVisible()
    })
  })

  test('successful login pushes to dashboard', async ({ page }) => {
    // stub login and subsequent auth/me call
    await page.route('**/api/v1/auth/token', route => {
      route.fulfill({ status: 200, contentType: 'application/json', body: '{}' })
    })
    await allowAuth(page, true)

    await page.goto('/signin')
    await page.getByTestId('login-username').fill('foo@bar.com')
    await page.getByTestId('login-password').fill('password123')
    await page.getByTestId('login-submit').click()

    await expect(page).toHaveURL('/dashboard')
  })
})

// Test Sign Up Page

test.describe('Sign Up Page', () => {
  test('should render all elements and allow navigation to Sign In', async ({ page }) => {
    await page.goto('/signup')

    await test.step('Verify page title and main elements', async () => {
      await expect(page.getByTestId('signup-title')).toHaveText('Criar conta')
      await expect(page.getByTestId('signup-fullname')).toBeVisible()
      await expect(page.getByTestId('signup-email')).toBeVisible()
      await expect(page.getByTestId('signup-password')).toBeVisible()
      await expect(page.getByTestId('signup-submit')).toBeVisible()
      await expect(page.getByTestId('signup-signin')).toBeVisible()
    })

    await test.step('Test navigation to sign in', async () => {
      await page.getByTestId('signup-signin').click()
      await expect(page).toHaveURL('/signin')
    })
  })

  test('should validate password requirements', async ({ page }) => {
    await page.goto('/signup')

    await test.step('Fill form with short password', async () => {
      await page.getByTestId('signup-fullname').fill('Test User')
      await page.getByTestId('signup-email').fill('test@example.com')
      await page.getByTestId('signup-password').fill('123') // Too short
    })

    await test.step('Attempt submission', async () => {
      await page.getByTestId('signup-submit').click()
    })

    await test.step('Verify form validation prevents submission', async () => {
      // HTML5 validation should prevent submission for short password
      await expect(page.getByTestId('signup-password')).toHaveValue('123')
      await expect(page).toHaveURL('/signup') // Should not navigate
    })
  })

  test('should handle complete form submission', async ({ page }) => {
    await page.goto('/signup')

    await test.step('Fill all required fields', async () => {
      await page.getByTestId('signup-fullname').fill('Test User')
      await page.getByTestId('signup-email').fill('test@example.com')
      await page.getByTestId('signup-password').fill('password123')
    })

    await test.step('Verify form is ready', async () => {
      await expect(page.getByTestId('signup-submit')).toBeEnabled()
      await expect(page.getByTestId('signup-fullname')).toHaveValue('Test User')
      await expect(page.getByTestId('signup-email')).toHaveValue('test@example.com')
      await expect(page.getByTestId('signup-password')).toHaveValue('password123')
    })

    // Note: Actual submission would require backend mocking
  })
})
