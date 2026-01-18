import { Card, CardHeader, CardContent } from "@/components/ui/card";
import { ResetPasswordForm } from "./ResetPasswordForm";

export default function ResetPasswordPage() {
  return (
    <div className="flex min-h-screen items-center justify-center bg-background">
      <Card className="w-full max-w-md shadow-lg border rounded-2xl bg-card">
        <CardHeader>
          <h1 className="text-center text-2xl font-bold font-serif mb-2">Redefinir senha</h1>
        </CardHeader>
        <CardContent className="space-y-6">
          <ResetPasswordForm />
        </CardContent>
      </Card>
    </div>
  );
}
